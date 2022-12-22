<?php

namespace Mrzkit\WpPluginSnippetCodeManager\Util;

class GeneralUtil
{
    /**
     * @desc
     * @param $data
     * @return array
     */
    public static function sanitizeArray($data)
    {
        if ( !is_array($data)) {
            return [];
        }

        $list = [];

        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $list[$key] = self::sanitizeArray($item);
            } else {
                $list[$key] = self::sanitizeText($item);
            }
        }

        return $list;
    }

    /**
     * @desc
     * @param $data
     * @return string
     */
    public static function sanitizeText($data)
    {
        $data = stripslashes_deep($data);

        return sanitize_text_field($data);
    }

}
