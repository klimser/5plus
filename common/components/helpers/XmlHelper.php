<?php
namespace common\components\helpers;

class XmlHelper
{
    /**
     * @param \SimpleXMLElement $xmlElement
     * @return array|string
     */
    private static function parseXmlElement(\SimpleXMLElement $xmlElement) {
        $out = [];
        if ($xmlElement->attributes()) {
            $out['@attributes'] = [];
            /** @var \SimpleXMLElement $value */
            foreach ($xmlElement->attributes() as $key => $value) $out['@attributes'][$key] = $value->__toString();
        }
        $multiTagMap = [];
        /** @var \SimpleXMLElement $child */
        foreach ($xmlElement->children() as $child) {
            if (isset($out[$child->getName()])) {
                if (!isset($multiTagMap[$child->getName()])) {
                    $out[$child->getName()] = [
                        $out[$child->getName()],
                        self::parseXmlElement($child),
                    ];
                    $multiTagMap[$child->getName()] = true;
                } else $out[$child->getName()][] = self::parseXmlElement($child);
            } else {
                $out[$child->getName()] = self::parseXmlElement($child);
            }
        }
        if (count($out) == 1 && isset($out['@attributes'])) {
            $body = $xmlElement->__toString();
            if ($body) $out[] = $body;
        }
        if (count($out) == 0) return $xmlElement->__toString();
        return $out;
    }

    /**
     * @param \SimpleXMLElement|string $xml
     * @return array|string
     */
    public static function xmlToArray($xml) {
        if (is_string($xml)) {
            $xml = trim($xml);
            $xml = str_replace("\r\n", "\n", $xml);
            $xml = simplexml_load_string($xml);
        }
        if ($xml instanceof \SimpleXMLElement) return self::parseXmlElement($xml);
        else return [];
    }

    /**
     * @param array $array
     * @return string
     */
    public static function arrayToXml(array $array): string
    {
        $xmlResult = '';
        foreach ($array as $key => $value) {
            if ($key === '@attributes' || $key === 'tag') continue;
            $xmlStruct = [];
            if (is_array($value) && array_key_exists('tag', $value)) $xmlStruct['tag'] = $value['tag'];
            else $xmlStruct['tag'] = $key;

            if (is_array($value)) {
                if (array_key_exists('@attributes', $value)) $xmlStruct['@attributes'] = $value['@attributes'];

                if (array_key_exists('body', $value)) {
                    if (is_array($value['body'])) $xmlStruct['body'] = self::arrayToXml($value['body']);
                    else $xmlStruct['body'] = htmlspecialchars($value['body'], ENT_NOQUOTES);
                } else {
                    $xmlStruct['body'] = self::arrayToXml($value);
                }
            } else {
                $xmlStruct['body'] = htmlspecialchars($value, ENT_NOQUOTES);
            }

            $propertiesString = '';
            if (array_key_exists('@attributes', $xmlStruct) && is_array($xmlStruct['@attributes'])) {
                foreach ($xmlStruct['@attributes'] as $attributeName => $attributeValue) {
                    $propertiesString .= ' ' . $attributeName . '="' . htmlspecialchars($attributeValue, ENT_QUOTES|ENT_HTML5, 'UTF-8') . '" ';
                }
            }
            $xmlResult .= '<' . $xmlStruct['tag'] . $propertiesString . ((!empty($xmlStruct['body']) || $xmlStruct['body'] === '0') ? '>' . $xmlStruct['body'] . '</' . $xmlStruct['tag'] . '>' : '/>');
        }
        return $xmlResult;
    }
}
