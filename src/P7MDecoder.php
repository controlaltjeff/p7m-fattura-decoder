<?php

namespace Controlaltjeff\P7MDecoder;

final class P7MDecoder
{
    /** @var array<int, array{from: string, to: string, distance: int}> */
    private static $lastCorrections = array();

    /** @return list<string> */
    private static function getValidTags()
    {
        return array(
            'ABI','AlCassa','AlboProfessionale','AlgoritmoCompressione','AliquotaIVA',
            'AliquotaRitenuta','Allegati','AltriDatiGestionali','Anagrafica','Arrotondamento',
            'Art73','Attachment','BIC','Beneficiario','BolloVirtuale','CAB','CAP',
            'CFQuietanzante','CapitaleSociale','Causale','CausalePagamento','CausaleTrasporto',
            'CedentePrestatore','CessionarioCommittente','CodEORI','CodUfficioPostale',
            'CodiceArticolo','CodiceCIG','CodiceCUP','CodiceCommessaConvenzione',
            'CodiceDestinatario','CodiceFiscale','CodicePagamento','CodiceTipo','CodiceValore',
            'Cognome','CognomeQuietanzante','Comune','CondizioniPagamento','Contatti',
            'ContattiTrasmittente','Data','DataDDT','DataDecorrenzaPenale','DataFatturaPrincipale',
            'DataFinePeriodo','DataInizioPeriodo','DataInizioTrasporto','DataIscrizioneAlbo',
            'DataLimitePagamentoAnticipato','DataOraConsegna','DataOraRitiro',
            'DataRiferimentoTerminiPagamento','DataScadenzaPagamento','DatiAnagrafici',
            'DatiAnagraficiVettore','DatiBeniServizi','DatiBollo','DatiCassaPrevidenziale',
            'DatiContratto','DatiConvenzione','DatiDDT','DatiFattureCollegate','DatiGenerali',
            'DatiGeneraliDocumento','DatiOrdineAcquisto','DatiPagamento','DatiRicezione',
            'DatiRiepilogo','DatiRitenuta','DatiSAL','DatiTrasmissione','DatiTrasporto',
            'DatiVeicoli','Denominazione','Descrizione','DescrizioneAttachment','DettaglioLinee',
            'DettaglioPagamento','Divisa','Email','EsigibilitaIVA','FatturaElettronica',
            'FatturaElettronicaBody','FatturaElettronicaHeader','FatturaPrincipale','Fax',
            'FormatoAttachment','FormatoTrasmissione','GiorniTerminiPagamento','IBAN',
            'IdCodice','IdDocumento','IdFiscaleIVA','IdPaese','IdTrasmittente','ImponibileCassa',
            'ImponibileImporto','Importo','ImportoBollo','ImportoContributoCassa',
            'ImportoPagamento','ImportoRitenuta','ImportoTotaleDocumento','Imposta','Indirizzo',
            'IndirizzoResa','IscrizioneREA','IstitutoFinanziario','MezzoTrasporto',
            'ModalitaPagamento','Natura','Nazione','Nome','NomeAttachment','NomeQuietanzante',
            'NumItem','Numero','NumeroCivico','NumeroColli','NumeroDDT',
            'NumeroFatturaPrincipale','NumeroIscrizioneAlbo','NumeroLicenzaGuida','NumeroLinea',
            'NumeroREA','PECDestinatario','PenalitaPagamentiRitardati','Percentuale','PesoLordo',
            'PesoNetto','PrezzoTotale','PrezzoUnitario','ProgressivoInvio','Provincia',
            'ProvinciaAlbo','Quantita','RappresentanteFiscale','RegimeFiscale',
            'RiferimentoAmministrazione','RiferimentoData','RiferimentoFase','RiferimentoNormativo',
            'RiferimentoNumero','RiferimentoNumeroLinea','RiferimentoTesto','Ritenuta',
            'ScontoMaggiorazione','ScontoPagamentoAnticipato','Sede','SocioUnico',
            'SoggettoEmittente','SpeseAccessorie','StabileOrganizzazione','StatoLiquidazione',
            'Telefono','TerzoIntermediarioOSoggettoEmittente','Tipo','TipoCassa',
            'TipoCessionePrestazione','TipoDato','TipoDocumento','TipoResa','TipoRitenuta',
            'Titolo','TitoloQuietanzante','TotalePercorso','Ufficio','UnitaMisura',
            'UnitaMisuraPeso',
        );
    }
    /**
     * Decode a .p7m file and return its XML content.
     *
     * @param string $path Path to the .p7m file
     *
     * @return string|null Decoded XML content, or null on failure
     */
    public static function decodeFile($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            error_log('P7MDecoder: file not found or unreadable: ' . $path);
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            error_log('P7MDecoder: failed to read file: ' . $path);
            return null;
        }

        return self::decode($content, $path);
    }

    /**
     * Decode a .p7m string content and return its XML.
     *
     * @param string $content Raw .p7m content (DER, PEM, Base64, or plain XML)
     *
     * @return string|null Decoded XML content, or null on failure
     */
    public static function decodeString($content)
    {
        return self::decode($content);
    }

    /**
     * Get the list of tag corrections applied during the last decode operation.
     *
     * @return array<int, array{from: string, to: string, distance: int}>
     */
    public static function getLastCorrections()
    {
        return self::$lastCorrections;
    }

    /**
     * @param string      $content
     * @param string|null $filePath
     *
     * @return string|null
     */
    private static function decode($content, $filePath = null)
    {
        self::$lastCorrections = array();
        $trimmed = trim($content);
        if ($trimmed === '') {
            return null;
        }

        if (self::isXml($trimmed)) {
            return $trimmed;
        }

        $xml = null;

        $execEnabled = true;
        $disabled = @ini_get('disable_functions');
        if (is_string($disabled) && $disabled !== '') {
            $execEnabled = !in_array('exec', array_map('trim', explode(',', $disabled)));
        }

        if ($execEnabled) {
            $xml = self::tryOpenSslBinary($trimmed, $filePath);
            if ($xml !== null) {
                return self::cleanXml($xml);
            }

            $xml = self::tryBase64DecodedBinary($trimmed);
            if ($xml !== null) {
                return self::cleanXml($xml);
            }

            $xml = self::tryPemFormat($trimmed, $filePath);
            if ($xml !== null) {
                return self::cleanXml($xml);
            }
        }

        $xml = self::tryPhpPkcs7Read($trimmed);
        if ($xml !== null) {
            return self::cleanXml($xml);
        }

        $xml = self::tryManualExtraction($trimmed);
        if ($xml !== null) {
            return self::cleanXml($xml);
        }

        $xml = self::tryTagCorrection($trimmed);
        if ($xml !== null) {
            return self::cleanXml($xml);
        }

        $xml = self::tryBase64ManualExtraction($trimmed);
        if ($xml !== null) {
            return self::cleanXml($xml);
        }

        return null;
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    private static function isXml($content)
    {
        if (strpos($content, '<?xml') !== 0 && strpos($content, '<') !== 0) {
            return false;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $result = $dom->loadXML($content);
        libxml_clear_errors();
        return $result && $dom->documentElement !== null;
    }

    /**
     * @param string      $content
     * @param string|null $filePath
     *
     * @return string|null
     */
    private static function tryOpenSslBinary($content, $filePath)
    {
        if ($filePath !== null) {
            return self::runOpenSslVerify($filePath, 'DER');
        }

        $tempInput = tempnam(sys_get_temp_dir(), 'p7m_');
        if ($tempInput === false) {
            return null;
        }

        file_put_contents($tempInput, $content);
        $result = self::runOpenSslVerify($tempInput, 'DER');
        @unlink($tempInput);
        return $result;
    }

    /**
     * @param string $content
     *
     * @return string|null
     */
    private static function tryBase64DecodedBinary($content)
    {
        $clean = str_replace(array("\r", "\n"), '', $content);
        if (!preg_match('/^[A-Za-z0-9\/+]*={0,2}$/', $clean)) {
            return null;
        }

        $decoded = base64_decode($clean, true);
        if ($decoded === false) {
            return null;
        }

        $tempInput = tempnam(sys_get_temp_dir(), 'p7m_der_');
        if ($tempInput === false) {
            return null;
        }

        file_put_contents($tempInput, $decoded);
        $result = self::runOpenSslVerify($tempInput, 'DER');
        @unlink($tempInput);
        return $result;
    }

    /**
     * @param string      $content
     * @param string|null $filePath
     *
     * @return string|null
     */
    private static function tryPemFormat($content, $filePath)
    {
        $trimmed = trim($content);

        if (strpos($trimmed, '-----BEGIN') === 0) {
            $inputPath = $filePath;
            if ($inputPath === null) {
                $inputPath = tempnam(sys_get_temp_dir(), 'p7m_pem_');
                if ($inputPath === false) {
                    return null;
                }
                file_put_contents($inputPath, $trimmed);
            }

            $result = self::runOpenSslVerify($inputPath, 'PEM');

            if ($filePath === null) {
                @unlink($inputPath);
            }

            return $result;
        }

        $clean = str_replace(array("\r", "\n"), '', $trimmed);
        if (!preg_match('/^[A-Za-z0-9\/+]*={0,2}$/', $clean)) {
            return null;
        }

        $pem = "-----BEGIN PKCS7-----\n" . chunk_split($clean, 64, "\n") . '-----END PKCS7-----';
        $tempInput = tempnam(sys_get_temp_dir(), 'p7m_pem_');
        if ($tempInput === false) {
            return null;
        }

        file_put_contents($tempInput, $pem);
        $result = self::runOpenSslVerify($tempInput, 'PEM');
        @unlink($tempInput);
        return $result;
    }

    /**
     * @param string $filePath
     * @param string $format
     *
     * @return string|null
     */
    private static function runOpenSslVerify($filePath, $format)
    {
        $tempOut = tempnam(sys_get_temp_dir(), 'xml_');
        if ($tempOut === false) {
            return null;
        }

        $cmd = sprintf(
            'openssl smime -verify -inform %s -in %s -noverify -out %s 2>/dev/null',
            escapeshellarg($format),
            escapeshellarg($filePath),
            escapeshellarg($tempOut)
        );
        exec($cmd, $output, $exitCode);

        if (file_exists($tempOut)) {
            $result = file_get_contents($tempOut);
            if ($result !== false && $result !== '') {
                @unlink($tempOut);
                return $result;
            }
        }
        if ($exitCode !== 0) {
            error_log('P7MDecoder: openssl smime verify warning (exit ' . $exitCode . ')');
        }

        $cmd = sprintf(
            'openssl cms -verify -inform %s -in %s -noverify -out %s 2>/dev/null',
            escapeshellarg($format),
            escapeshellarg($filePath),
            escapeshellarg($tempOut)
        );
        exec($cmd, $output, $exitCode);

        if (file_exists($tempOut)) {
            $result = file_get_contents($tempOut);
            if ($result !== false && $result !== '') {
                @unlink($tempOut);
                return $result;
            }
        }
        if ($exitCode !== 0) {
            error_log('P7MDecoder: openssl cms verify warning (exit ' . $exitCode . ')');
        }

        @unlink($tempOut);
        return null;
    }

    /**
     * @param string $content
     *
     * @return string|null
     */
    private static function tryPhpPkcs7Read($content)
    {
        if (!function_exists('openssl_pkcs7_verify')) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'p7m_php_');
        $tempOut = tempnam(sys_get_temp_dir(), 'xml_php_');
        if ($tempFile === false || $tempOut === false) {
            @unlink($tempFile);
            @unlink($tempOut);
            return null;
        }

        file_put_contents($tempFile, $content);

        $result = @openssl_pkcs7_verify(
            $tempFile,
            PKCS7_NOVERIFY,
            '/dev/null',
            array(),
            '/dev/null',
            $tempOut
        );

        if ($result === true) {
            $outContent = file_get_contents($tempOut);
            if ($outContent !== false && $outContent !== '') {
                @unlink($tempFile);
                @unlink($tempOut);
                return $outContent;
            }
        }

        @unlink($tempFile);
        @unlink($tempOut);
        return null;
    }

    /**
     * @param string $content
     *
     * @return string|null
     */
    private static function tryTagCorrection($content)
    {
        $fragment = self::extractXmlFragment($content);
        if ($fragment === null || $fragment === '') {
            return null;
        }

        if (self::isXml($fragment)) {
            return null;
        }

        $cleaned = self::cleanTagNames($fragment);
        if (self::isXml($cleaned)) {
            return $cleaned;
        }

        $cleaned = preg_replace('/[^\x20-\x7E]/', '', $cleaned);
        if ($cleaned === null) {
            return null;
        }
        if (self::isXml($cleaned)) {
            return $cleaned;
        }

        $validSet = array_flip(self::getValidTags());

        preg_match_all('/<\/?([A-Za-z][A-Za-z0-9]*)/', $cleaned, $matches);
        $tags = array_unique($matches[1]);

        $fixed = $cleaned;
        $corrections = 0;

        foreach ($tags as $tag) {
            if (isset($validSet[$tag])) {
                continue;
            }

            $bestTag = null;
            $bestDist = PHP_INT_MAX;
            foreach (self::getValidTags() as $valid) {
                $lenDiff = abs(strlen($valid) - strlen($tag));
                if ($lenDiff > 2) {
                    continue;
                }
                $dist = levenshtein($tag, $valid);
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $bestTag = $valid;
                }
            }

            if ($bestTag !== null && $bestDist <= 1 && strlen($tag) >= 4) {
                error_log('P7MDecoder: tag correction: <' . $tag . '> -> <' . $bestTag . '> (distance ' . $bestDist . ')');
                self::$lastCorrections[] = array(
                    'from' => $tag,
                    'to' => $bestTag,
                    'distance' => $bestDist,
                );
                $fixed = str_replace('<' . $tag . '>', '<' . $bestTag . '>', $fixed);
                $fixed = str_replace('</' . $tag . '>', '</' . $bestTag . '>', $fixed);
                $corrections++;
            }
        }

        if ($corrections > 0 && self::isXml($fixed)) {
            error_log('P7MDecoder: tag correction applied ' . $corrections . ' fix(es)');
            return $fixed;
        }

        return null;
    }

    /**
     * Cleans tag names by matching across binary garbage inside tags.
     * Uses known valid tags to reconstruct correct tag names.
     *
     * @param string $xml
     *
     * @return string
     */
    private static function cleanTagNames($xml)
    {
        $result = preg_replace_callback(
            '/<(\/?)([A-Za-z][A-Za-z0-9]*)[^>]*?([A-Za-z0-9]*)>/',
            function ($matches) {
                $slash = $matches[1];
                $prefix = $matches[2];
                $suffix = $matches[3];
                $fullTag = $prefix . $suffix;

                $validSet = array_flip(self::getValidTags());
                if (isset($validSet[$fullTag])) {
                    return '<' . $slash . $fullTag . '>';
                }

                foreach (self::getValidTags() as $valid) {
                    if (strpos($valid, $prefix) === 0 && substr($valid, -strlen($suffix)) === $suffix) {
                        return '<' . $slash . $valid . '>';
                    }
                }

                return $matches[0];
            },
            $xml
        );

        return ($result !== null ? $result : $xml);
    }

    /**
     * @param string $content
     *
     * @return string|null
     */
    private static function tryManualExtraction($content)
    {
        $result = self::extractXmlFragment($content);
        if ($result === null || $result === '') {
            return null;
        }

        if (self::isXml($result)) {
            return $result;
        }

        return null;
    }

    /**
     * @param string $content
     *
     * @return string|null
     */
    private static function tryBase64ManualExtraction($content)
    {
        $clean = str_replace(array("\r", "\n", ' '), '', $content);
        if (!preg_match('/^[A-Za-z0-9\/+]*={0,2}$/', $clean)) {
            return null;
        }

        $decoded = base64_decode($clean, true);
        if ($decoded === false) {
            return null;
        }

        $result = self::extractXmlFragment($decoded);
        if ($result === null || $result === '') {
            return null;
        }

        if (self::isXml($result)) {
            return $result;
        }

        return null;
    }

    /**
     * @param string $data
     *
     * @return string|null
     */
    private static function extractXmlFragment($data)
    {
        $candidates = array();

        $pos = strpos($data, '<?xml ');
        if ($pos !== false) {
            $candidates[] = $pos;
        }

        $pos = strpos($data, '<p:FatturaElettronica');
        if ($pos !== false) {
            $candidates[] = $pos;
        }

        $pos = strpos($data, '<FatturaElettronicaSemplificata');
        if ($pos !== false) {
            $candidates[] = $pos;
        }

        $pos = strpos($data, '<FatturaElettronica');
        if ($pos !== false) {
            $candidates[] = $pos;
        }

        $pos = strpos($data, 'FatturaElettronica');
        if ($pos !== false) {
            $start = strrpos(substr($data, 0, $pos), '<');
            if ($start !== false) {
                $candidates[] = $start;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        $start = min($candidates);
        $xml = substr($data, $start);

        $endMarkers = array(
            '</p:FatturaElettronica>',
            '</FatturaElettronica>',
            '</FatturaElettronicaSemplificata>',
        );

        $end = false;
        foreach ($endMarkers as $marker) {
            $pos = strrpos($xml, $marker);
            if ($pos !== false) {
                $end = $pos + strlen($marker);
                break;
            }
        }

        if ($end === false) {
            preg_match_all('/<\/.+?>/', $xml, $matches, PREG_OFFSET_CAPTURE);
            $lastMatch = end($matches[0]);
            if ($lastMatch !== false) {
                $end = $lastMatch[1] + strlen($lastMatch[0]);
            }
        }

        if ($end === false || $end === 0) {
            return null;
        }

        return substr($xml, 0, $end);
    }

    /**
     * @param string $xml
     *
     * @return string
     */
    private static function cleanXml($xml)
    {
        $xml = (string) preg_replace('/[^\x20-\x7E\r\n\t]/', '', $xml);

        $xml = (string) preg_replace('/\s+/', ' ', $xml);

        if (stripos($xml, '<[CDATA[') !== false) {
            $xml = str_replace(']]>', '', str_replace('<[CDATA[', '', $xml));
        }

        $xml = str_replace('UnitaMisurax', 'UnitaMisura', $xml);
        $xml = str_replace('DettHaglioLinee', 'DettaglioLinee', $xml);
        $xml = str_replace('DeJttaglioLinee', 'DettaglioLinee', $xml);
        $xml = str_replace('<D escrizione', '<Descrizione', $xml);
        $xml = str_replace('<Desc rizione', '<Descrizione', $xml);
        $xml = str_replace('<Descri zione', '<Descrizione', $xml);
        $xml = str_replace('UnitaMisurta', 'UnitaMisura', $xml);
        $xml = str_replace('UnitaMisuzra', 'UnitaMisura', $xml);
        $xml = str_replace('UnitaMisurya', 'UnitaMisura', $xml);
        $xml = str_replace('UnitaMisurah', 'UnitaMisura', $xml);
        $xml = str_replace('</UnitaMisurah', '</UnitaMisura', $xml);
        $xml = str_replace('</hIBAN>', '</IBAN>', $xml);
        $xml = str_replace('DataInizioP eriodo', 'DataInizioPeriodo', $xml);
        $xml = str_replace('RiRferimentoTesto', 'RiferimentoTesto', $xml);
        $xml = str_replace('RiferimentoTe-sto', 'RiferimentoTesto', $xml);
        $xml = str_replace('Num eroLinea', 'NumeroLinea', $xml);
        $xml = str_replace('Ali quotaIVA', 'AliquotaIVA', $xml);
        $xml = str_replace('Aliqu otaIVA', 'AliquotaIVA', $xml);
        $xml = str_replace('DettaglioLine>', 'DettaglioLinee>', $xml);
        $xml = str_replace('DettaglioLine ', 'DettaglioLinee ', $xml);
        $xml = str_replace('AltriDa tiGestionali', 'AltriDatiGestionali', $xml);
        $xml = (string) preg_replace('/<UnitaMi(\s|>)/', '<UnitaMisura$1', $xml);
        $xml = str_replace('<Quant2ita', '<Quantita', $xml);
        $xml = str_replace('</Quant2ita', '</Quantita', $xml);
        $xml = str_replace('<Unita~Misura', '<UnitaMisura', $xml);
        $xml = str_replace('<UnitaMi|sura', '<UnitaMisura', $xml);
        $xml = str_replace('<AltriDa|tiGestionali', '<AltriDatiGestionali', $xml);
        $xml = str_replace('</Prezzo Totale>', '</PrezzoTotale>', $xml);
        $xml = str_replace('</Prez zoTotale>', '</PrezzoTotale>', $xml);
        $xml = str_replace('</DettaglioLinee e>', '</DettaglioLinee>', $xml);
        $xml = str_replace('<Condi?zioniPagamento>', '<CondizioniPagamento>', $xml);
        $xml = (string) preg_replace('/<UnitaMisura>([^<]+)<t\/(UnitaMisura)>/', '<UnitaMisura>$1</$2>', $xml);

        return trim($xml);
    }
}
