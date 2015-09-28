<?php

    function remove_mlf_suffix ($file, $languages) {
        $xml = new DOMDocument();
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;
        $xml->load($file) or die("Error: Cannot create object");

        $post_types = $xml->getElementsByTagName('post_type');

        for ($i = 0; $i < $post_types->length; $i++) {
            $pt = $post_types->item($i)->nodeValue;

            foreach ($languages as $lang) {
                $pos = strpos($pt, '_t_'.$lang);
                if ($pos !== false) {
                    $pt = str_replace( '_t_'.$lang, '', $pt );
                    break;
                }
            }

            $post_types->item($i)->nodeValue = $pt;
        }

        $guid = $xml->getElementsByTagName('guid');

        for ($i = 0; $i < $guid->length; $i++) {
            $link = $guid->item($i)->nodeValue;

            foreach ($languages as $lang) {
                $pos = strpos($link, '_t_'.$lang);
                if ($pos !== false) {
                    $link = str_replace( '_t_'.$lang, '', $link );
                    break;
                }
            }

            $guid->item($i)->nodeValue = htmlentities($link);
        }

        $xml->save($file);
    }

?>