<?php
/**
 * includes/fatura_xml.php
 * Python tarafındaki utils/fatura_xml.py (FaturaXML sınıfı) karşılığı.
 *
 * ÖNEMLİ NOT: Orijinal Flask projesinde bu dosya (utils/fatura_xml.py) ve
 * ona karşılık gelen templates/fatura_xml.html hiçbir route tarafından
 * KULLANILMIYORDU - app.py içinde hiç import/çağrı edilmemiş, tamamen
 * ölü kod olarak duruyordu. Üstelik fatura_listesi.html şablonu
 * `url_for('fatura_xml_olustur', ...)` ve `url_for('fatura_xml_indir', ...)`
 * için hiç var olmayan route'lara link veriyordu - yani orijinal
 * uygulamada en az bir fatura varken /faturalar sayfasını açmak
 * Jinja2 BuildError'ı ile ÇÖKÜYORDU. Burada bu ölü kodu temel alarak
 * gerçek, çalışan bir XML üretme/indirme özelliği kuruldu.
 */

class FaturaXML
{
    private array $fatura;
    private array $cari;
    private array $detaylar;
    private array $firma;

    public function __construct(array $fatura, array $cari, array $detaylar, array $firma)
    {
        $this->fatura = $fatura;
        $this->cari = $cari;
        $this->detaylar = $detaylar;
        $this->firma = $firma;
    }

    private function birimKodu(?string $birim): string
    {
        $map = [
            'ADET' => 'UNIT', 'KG' => 'KGM', 'METRE' => 'MTR',
            'SAAT' => 'HUR', 'LİTRE' => 'LTR', 'PAKET' => 'PK', 'KUTU' => 'BX',
        ];
        return $map[$birim] ?? 'UNIT';
    }

    private function odemeKodu(?string $odemeTuru): string
    {
        $map = [
            'NAKİT' => '1', 'KREDİ KARTI' => '2', 'BANKA HAVALESİ' => '3',
            'ÇEK' => '4', 'KAPIDA ÖDEME' => '5',
        ];
        return $map[$odemeTuru] ?? '1';
    }

    private function n(?string $s): string
    {
        return (string)($s ?? '');
    }

    private function f(?float $n): string
    {
        return number_format((float)($n ?? 0), 2, '.', '');
    }

    /** UBL-TR 2.1 uyumlu XML üretir ve pretty-print edilmiş string olarak döndürür */
    public function olustur(): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', 'Invoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $dom->appendChild($root);

        $cbc = fn($name, $text) => $this->el($dom, 'cbc:' . $name, $text);
        $cac = fn($name) => $dom->createElement('cac:' . $name);

        // ===== 1. FATURA BİLGİLERİ =====
        // NOT (18 Temmuz 2026): GİB'in UBL-TR standardında ProfileID (senaryo)
        // alanı için geçerli değerler TAM İSİMLERDİR - "TEMELFATURA",
        // "TICARIFATURA", "EARSIVFATURA", "IHRACAT" vb. Uygulamanın kendi
        // içindeki "senaryo" seçim kutusu ise kullanıcıya kolay gelsin diye
        // "TEMEL"/"TİCARİ"/"İADE" gibi kısa Türkçe etiketler kullanıyor -
        // bunlar doğrudan GİB'e gönderilirse şematron reddederdi. Aşağıdaki
        // eşleme bunu düzeltiyor.
        $profileIdMap = [
            'TEMEL'  => 'TEMELFATURA',
            'TİCARİ' => 'TICARIFATURA',
            'İADE'   => 'TEMELFATURA', // İADE bir ProfileID değeri değil - bu durumda geçerli bir senaryo (TEMELFATURA) + aşağıdaki InvoiceTypeCode=IADE kullanılır
        ];
        $senaryoHam = $this->fatura['fatura_senaryosu'] ?? 'TEMEL';
        $profileId = $profileIdMap[$senaryoHam] ?? 'TEMELFATURA';

        $root->appendChild($cbc('UBLVersionID', '2.1'));
        $root->appendChild($cbc('CustomizationID', 'TR1.2'));
        $root->appendChild($cbc('ProfileID', $profileId));
        $root->appendChild($cbc('ID', $this->n($this->fatura['fatura_no'])));
        $root->appendChild($cbc('CopyIndicator', 'false'));
        $root->appendChild($cbc('UUID', $this->fatura['gib_uuid'] ?: $this->generateUuid()));

        $tarih = strtotime($this->fatura['fatura_tarihi']);
        $root->appendChild($cbc('IssueDate', date('Y-m-d', $tarih)));
        $root->appendChild($cbc('IssueTime', date('H:i:s', $tarih)));

        // NOT: GİB'in InvoiceTypeCode kod listesinde geçerli değerler SATIS,
        // IADE, TEVKIFAT, ISTISNA, OZELMATRAH, IHRACKAYITLI vb.'dir - "ALIS"
        // GEÇERLİ BİR KOD DEĞİLDİR. Kavramsal olarak da e-Fatura SATICI
        // tarafından üretilir - bir "alış faturası"nın e-Fatura XML'ini alıcı
        // taraf üretmez (tedarikçiden zaten gelir). Bu satır şu an sadece
        // dahili/görsel bir çıktı ürettiği için ALIS değeri çalışıyor gibi
        // görünüyor, ama bu XML gerçekten bir Özel Entegratör'e/GİB'e
        // gönderilecekse ALIŞ faturaları için bu akışın hiç kullanılmaması
        // gerekir. Efe'nin bu özelliği nasıl kullanmayı planladığı henüz
        // netleşmedi (bkz. README "Bilinen Sınırlamalar").
        $turMap = ['SATIŞ' => 'SATIS', 'ALIŞ' => 'ALIS', 'İADE' => 'IADE'];
        $root->appendChild($cbc('InvoiceTypeCode', $turMap[$this->fatura['fatura_turu']] ?? 'SATIS'));
        $root->appendChild($cbc('DocumentCurrencyCode', $this->n($this->fatura['para_birimi'] ?: 'TRY')));
        $root->appendChild($cbc('LineCountNumeric', (string)count($this->detaylar)));

        // ===== 2. SATICI BİLGİLERİ =====
        $supplier = $cac('AccountingSupplierParty');
        $party = $cac('Party');
        $party->appendChild($cbc('WebsiteURI', $this->n($this->firma['website'] ?? '')));

        $partyId = $cac('PartyIdentification');
        $partyId->appendChild($cbc('ID', $this->n($this->firma['vergi_no'] ?? '')));
        $party->appendChild($partyId);

        $partyName = $cac('PartyName');
        $partyName->appendChild($cbc('Name', $this->n($this->firma['unvan'] ?? '')));
        $party->appendChild($partyName);

        $address = $cac('PostalAddress');
        $address->appendChild($cbc('CityName', $this->n($this->firma['sehir'] ?? '')));
        $country = $cac('Country');
        $country->appendChild($cbc('Name', 'Türkiye'));
        $address->appendChild($country);
        $party->appendChild($address);

        $taxScheme = $cac('PartyTaxScheme');
        $taxScheme->appendChild($cbc('TaxSchemeName', $this->n($this->firma['vergi_dairesi'] ?? '')));
        $party->appendChild($taxScheme);

        $supplier->appendChild($party);
        $root->appendChild($supplier);

        // ===== 3. ALICI BİLGİLERİ =====
        $customer = $cac('AccountingCustomerParty');
        $customerParty = $cac('Party');

        $custPartyId = $cac('PartyIdentification');
        $custPartyId->appendChild($cbc('ID', $this->n($this->cari['vergi_no'] ?? '')));
        $customerParty->appendChild($custPartyId);

        $custPartyName = $cac('PartyName');
        $custPartyName->appendChild($cbc('Name', $this->n($this->cari['unvan'] ?? '')));
        $customerParty->appendChild($custPartyName);

        $custAddress = $cac('PostalAddress');
        $custAddress->appendChild($cbc('CityName', $this->n($this->cari['vergi_dairesi'] ?? '')));
        $custCountry = $cac('Country');
        $custCountry->appendChild($cbc('Name', 'Türkiye'));
        $custAddress->appendChild($custCountry);
        $customerParty->appendChild($custAddress);

        $custTax = $cac('PartyTaxScheme');
        $custTax->appendChild($cbc('TaxSchemeName', $this->n($this->cari['vergi_dairesi'] ?? '')));
        $customerParty->appendChild($custTax);

        $customer->appendChild($customerParty);
        $root->appendChild($customer);

        // ===== 4. ÖDEME BİLGİLERİ =====
        $payment = $cac('PaymentMeans');
        $payment->appendChild($cbc('PaymentMeansCode', $this->odemeKodu($this->fatura['odeme_turu'] ?? null)));
        if (!empty($this->fatura['vade_tarihi'])) {
            $payment->appendChild($cbc('PaymentDueDate', date('Y-m-d', strtotime($this->fatura['vade_tarihi']))));
        }
        $root->appendChild($payment);

        // ===== 5. TOPLAM BİLGİLERİ =====
        $paraBirimi = $this->fatura['para_birimi'] ?: 'TRY';
        $legalTotal = $cac('LegalMonetaryTotal');

        $lineExt = $cbc('LineExtensionAmount', $this->f($this->fatura['ara_toplam'] ?? 0));
        $lineExt->setAttribute('currencyID', $paraBirimi);
        $legalTotal->appendChild($lineExt);

        $taxExclusive = $cbc('TaxExclusiveAmount', $this->f(($this->fatura['ara_toplam'] ?? 0) - ($this->fatura['iskonto_tutari'] ?? 0)));
        $taxExclusive->setAttribute('currencyID', $paraBirimi);
        $legalTotal->appendChild($taxExclusive);

        $taxInclusive = $cbc('TaxInclusiveAmount', $this->f($this->fatura['genel_toplam'] ?? 0));
        $taxInclusive->setAttribute('currencyID', $paraBirimi);
        $legalTotal->appendChild($taxInclusive);

        if (($this->fatura['iskonto_tutari'] ?? 0) > 0) {
            $allowance = $cbc('AllowanceTotalAmount', $this->f($this->fatura['iskonto_tutari']));
            $allowance->setAttribute('currencyID', $paraBirimi);
            $legalTotal->appendChild($allowance);
        }

        $payable = $cbc('PayableAmount', $this->f($this->fatura['genel_toplam'] ?? 0));
        $payable->setAttribute('currencyID', $paraBirimi);
        $legalTotal->appendChild($payable);
        $root->appendChild($legalTotal);

        // ===== 6. VERGİ BİLGİLERİ =====
        $taxTotal = $cac('TaxTotal');
        $taxAmount = $cbc('TaxAmount', $this->f($this->fatura['vergi_tutari'] ?? 0));
        $taxAmount->setAttribute('currencyID', $paraBirimi);
        $taxTotal->appendChild($taxAmount);

        $taxSubtotal = $cac('TaxSubtotal');
        $taxable = $cbc('TaxableAmount', $this->f(($this->fatura['ara_toplam'] ?? 0) - ($this->fatura['iskonto_tutari'] ?? 0)));
        $taxable->setAttribute('currencyID', $paraBirimi);
        $taxSubtotal->appendChild($taxable);

        $taxSubAmount = $cbc('TaxAmount', $this->f($this->fatura['vergi_tutari'] ?? 0));
        $taxSubAmount->setAttribute('currencyID', $paraBirimi);
        $taxSubtotal->appendChild($taxSubAmount);

        $taxCategory = $cac('TaxCategory');
        $taxSchemeElem = $cac('TaxScheme');
        $taxSchemeElem->appendChild($cbc('Name', 'KDV'));
        $taxCategory->appendChild($taxSchemeElem);
        $taxCategory->appendChild($cbc('TaxTypeCode', 'KDV'));
        $taxCategory->appendChild($cbc('TaxRate', (string)($this->fatura['vergi_orani'] ?? 20)));
        $taxSubtotal->appendChild($taxCategory);

        $taxTotal->appendChild($taxSubtotal);
        $root->appendChild($taxTotal);

        // ===== 7. FATURA KALEMLERİ =====
        $idx = 1;
        foreach ($this->detaylar as $detay) {
            $line = $cac('InvoiceLine');
            $line->appendChild($cbc('ID', (string)$idx));

            if (!empty($detay['aciklama'])) {
                $line->appendChild($cbc('Note', $this->n($detay['aciklama'])));
            }

            $quantity = $cbc('InvoicedQuantity', $this->f($detay['miktar'] ?? 0));
            $quantity->setAttribute('unitCode', $this->birimKodu($detay['birim'] ?? null));
            $line->appendChild($quantity);

            $lineAmount = $cbc('LineExtensionAmount', $this->f($detay['ara_toplam'] ?? 0));
            $lineAmount->setAttribute('currencyID', $paraBirimi);
            $line->appendChild($lineAmount);

            $item = $cac('Item');
            $item->appendChild($cbc('Name', $this->n($detay['urun_adi'] ?? '')));
            if (!empty($detay['urun_kodu'])) {
                $item->appendChild($cbc('Description', 'KOD: ' . $detay['urun_kodu']));
                $sellerId = $cac('SellersItemIdentification');
                $sellerId->appendChild($cbc('ID', $this->n($detay['urun_kodu'])));
                $item->appendChild($sellerId);
            }
            $line->appendChild($item);

            $price = $cac('Price');
            $priceAmount = $cbc('PriceAmount', $this->f($detay['birim_fiyati'] ?? 0));
            $priceAmount->setAttribute('currencyID', $paraBirimi);
            $price->appendChild($priceAmount);
            $line->appendChild($price);

            $root->appendChild($line);
            $idx++;
        }

        return $dom->saveXML();
    }

    private function el(DOMDocument $dom, string $name, string $text): DOMElement
    {
        $element = $dom->createElement($name);
        $element->appendChild($dom->createTextNode($text));
        return $element;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }
}
