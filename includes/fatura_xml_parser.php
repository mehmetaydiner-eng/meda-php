<?php
/**
 * includes/fatura_xml_parser.php
 *
 * Efe'nin isteği üzerine (19 Temmuz 2026): GİB e-Fatura (UBL-TR) XML
 * dosyasından bir ALIŞ faturasını ayrıştırıp; tedarikçi (cari), fatura
 * bilgileri ve ürün kalemlerini çıkarır. Sadece OKUMA yapar - hiçbir
 * veritabanı yazması burada olmuz (o iş fatura_xml_kaydet.php'de,
 * kullanıcı önizlemeyi onayladıktan SONRA yapılıyor).
 */

class FaturaXmlParser
{
    private DOMXPath $xpath;

    /**
     * @throws RuntimeException XML okunamazsa veya geçerli bir UBL-TR
     *         Invoice değilse
     */
    public function __construct(string $xmlIcerik)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $basarili = $dom->loadXML($xmlIcerik, LIBXML_NONET | LIBXML_NOENT);
        libxml_clear_errors();

        if (!$basarili) {
            throw new RuntimeException('XML dosyası okunamadı - dosya bozuk veya geçerli bir XML değil.');
        }

        $this->xpath = new DOMXPath($dom);
        $this->xpath->registerNamespace('n1', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $this->xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $this->xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        if ($this->xpath->query('/n1:Invoice')->length === 0) {
            throw new RuntimeException('Bu bir e-Fatura (UBL-TR Invoice) XML\'i değil.');
        }
    }

    private function metin(string $sorgu, ?DOMNode $baglam = null): string
    {
        $sonuc = $baglam ? $this->xpath->query($sorgu, $baglam) : $this->xpath->query($sorgu);
        return $sonuc->length > 0 ? trim($sonuc->item(0)->textContent) : '';
    }

    /**
     * XML'i ayrıştırıp tüm faturayı tek bir dizi olarak döndürür.
     * Sadece okuma - hiçbir veritabanı işlemi yapmaz.
     */
    public function ayristir(): array
    {
        return [
            'tedarikci' => $this->tedarikciBilgisi(),
            'fatura'    => $this->faturaBilgisi(),
            'kalemler'  => $this->kalemler(),
        ];
    }

    private function tedarikciBilgisi(): array
    {
        $party = $this->xpath->query('/n1:Invoice/cac:AccountingSupplierParty/cac:Party')->item(0);
        if (!$party) {
            throw new RuntimeException('XML içinde tedarikçi (AccountingSupplierParty) bilgisi bulunamadı.');
        }

        // VKN önce PartyIdentification[@schemeID='VKN'] içinde aranır, yoksa
        // TCKN olabilir (şahıs firması) - ikisi de aynı alanda tutulur.
        $vkn = $this->metin('cac:PartyIdentification/cbc:ID[@schemeID="VKN"]', $party);
        if ($vkn === '') {
            $vkn = $this->metin('cac:PartyIdentification/cbc:ID[@schemeID="TCKN"]', $party);
        }

        $unvan = $this->metin('cac:PartyName/cbc:Name', $party);

        $sokak = $this->metin('cac:PostalAddress/cbc:StreetName', $party);
        $ilce  = $this->metin('cac:PostalAddress/cbc:CitySubdivisionName', $party);
        $il    = $this->metin('cac:PostalAddress/cbc:CityName', $party);
        $adresParcalari = array_filter([$sokak, $ilce, $il]);
        $adres = implode(' ', $adresParcalari);
        // XML'deki adres alanı bazen zaten satır sonlarıyla geliyor - tek
        // satıra indirgiyoruz, veritabanındaki "adres" tek satırlık bir alan.
        $adres = preg_replace('/\s+/', ' ', $adres);

        $vergiDairesi = $this->metin('cac:PartyTaxScheme/cac:TaxScheme/cbc:Name', $party);
        $telefon = $this->metin('cac:Contact/cbc:Telephone', $party);
        $email   = $this->metin('cac:Contact/cbc:ElectronicMail', $party);

        return [
            'vkn'           => $vkn,
            'unvan'         => $unvan,
            'adres'         => trim($adres),
            'vergi_dairesi' => $vergiDairesi,
            'telefon'       => $telefon,
            'email'         => $email,
        ];
    }

    private function faturaBilgisi(): array
    {
        $faturaNo = $this->metin('/n1:Invoice/cbc:ID');
        $uuid     = $this->metin('/n1:Invoice/cbc:UUID');
        $tarih    = $this->metin('/n1:Invoice/cbc:IssueDate'); // YYYY-MM-DD
        $paraBirimi = $this->metin('/n1:Invoice/cbc:DocumentCurrencyCode') ?: 'TRY';
        $genelToplam = $this->metin('/n1:Invoice/cac:LegalMonetaryTotal/cbc:PayableAmount');
        $araToplam   = $this->metin('/n1:Invoice/cac:LegalMonetaryTotal/cbc:LineExtensionAmount');
        $kdvToplam   = $this->metin('/n1:Invoice/cac:TaxTotal/cbc:TaxAmount');

        return [
            'fatura_no'    => $faturaNo,
            'uuid'         => $uuid,
            'tarih'        => $tarih,
            'para_birimi'  => $paraBirimi,
            'ara_toplam'   => (float)$araToplam,
            'kdv_toplam'   => (float)$kdvToplam,
            'genel_toplam' => (float)$genelToplam,
        ];
    }

    private function kalemler(): array
    {
        $satirlar = $this->xpath->query('/n1:Invoice/cac:InvoiceLine');
        $kalemler = [];

        foreach ($satirlar as $satir) {
            $stokKodu = $this->metin('cac:Item/cac:SellersItemIdentification/cbc:ID', $satir);
            $urunAdi  = $this->metin('cac:Item/cbc:Name', $satir);
            $miktar   = (float)$this->metin('cbc:InvoicedQuantity', $satir);
            $birimKodu = $this->birimKoduTurkce($this->birimCode($satir));
            $birimFiyat = (float)$this->metin('cac:Price/cbc:PriceAmount', $satir);
            $satirToplam = (float)$this->metin('cbc:LineExtensionAmount', $satir);
            $kdvOrani = (float)$this->metin('cac:TaxTotal/cac:TaxSubtotal/cbc:Percent', $satir);
            $kdvTutari = (float)$this->metin('cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', $satir);

            // Bazı GİB faturalarında Item/Name alanı çok uzun oluyor (seri no,
            // garanti bilgisi vb. de içine sıkıştırılmış oluyor) - olduğu gibi
            // bırakıyoruz, kullanıcı önizlemede düzenleyebilir.
            $kalemler[] = [
                'stok_kodu'     => $stokKodu,
                'urun_adi'      => $urunAdi,
                'miktar'        => $miktar,
                'birim'         => $birimKodu,
                'birim_fiyat'   => $birimFiyat,
                'satir_toplam'  => $satirToplam,
                'kdv_orani'     => $kdvOrani,
                'kdv_tutari'    => $kdvTutari,
                // Efe'nin isteği: satış fiyatı = alış fiyatının %30 fazlası (öneri - önizlemede değiştirilebilir)
                'onerilen_satis_fiyati' => round($birimFiyat * 1.30, 2),
            ];
        }

        return $kalemler;
    }

    private function birimCode(DOMNode $satir): string
    {
        $sonuc = $this->xpath->query('cbc:InvoicedQuantity/@unitCode', $satir);
        return $sonuc->length > 0 ? $sonuc->item(0)->textContent : 'C62';
    }

    /**
     * GİB'in UN/ECE birim kodlarından en sık kullanılanları uygulamanın
     * kendi "birim" alanındaki Türkçe karşılıklarına çeviriyor. Listede
     * olmayan bir kod gelirse, kodu olduğu gibi bırakıyoruz (kullanıcı
     * önizlemede düzeltebilir).
     */
    private function birimKoduTurkce(string $kod): string
    {
        $harita = [
            'C62' => 'ADET',
            'KGM' => 'KG',
            'GRM' => 'GRAM',
            'LTR' => 'LİTRE',
            'MTR' => 'METRE',
            'MTQ' => 'M3',
            'MTK' => 'M2',
            'BX'  => 'KUTU',
            'PA'  => 'PAKET',
            'SET' => 'SET',
            'PR'  => 'ÇİFT',
        ];
        return $harita[$kod] ?? 'ADET';
    }
}
