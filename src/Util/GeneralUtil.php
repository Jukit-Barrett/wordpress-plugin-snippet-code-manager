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
                $list[$key] = sanitize_text_field($item);
            }
        }

        return $list;
    }

}
