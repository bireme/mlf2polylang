<?php

    function remove_mlf_suffix($file, $languages) {
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

    function merge_output_files($files) {
        global $help;

        if ( count( $files ) < 2 )
            exit($help);
        else {
            $xml = new DOMDocument();
            $xml->formatOutput = true;
            $xml->preserveWhiteSpace = false;
            $xml->load($files[0]) or die("Error: Cannot create object");

            $authors = array();
            $users = $xml->getElementsByTagName('author');

            foreach ($users as $user)
                $authors[] = (int) $user->getElementsByTagName('author_id')->item(0)->nodeValue;

            foreach($files as $index => $file) {
                if ( $index == 0 ) continue;

                $content = new DOMDocument();
                $content->formatOutput = true;
                $content->preserveWhiteSpace = false;
                $content->load($file) or die("Error: Cannot create object");

                // append authors
                $users = $content->getElementsByTagName('author');
                foreach ($users as $user){
                    $author = (int) $user->getElementsByTagName('author_id')->item(0)->nodeValue;
                    if ( !in_array($author, $authors) ) {
                        $authors[] = $author;
                        $node = $xml->importNode($user, true);
                        $xml->getElementsByTagName('channel')->item(0)->appendChild($node);
                    }
                }

                // append items
                $items = $content->getElementsByTagName('item');
                foreach ($items as $item) {
                    $node = $xml->importNode($item, true);
                    $xml->getElementsByTagName('channel')->item(0)->appendChild($node);
                }

                // append post_translations terms
                $terms = $content->getElementsByTagName('term');
                foreach ($terms as $key => $term) {
                    $taxonomy = $term->getElementsByTagName('term_taxonomy')->item(0)->nodeValue;
                    if ( 'post_translations' == $taxonomy ) {
                        $node = $xml->importNode($term, true);
                        $xml->getElementsByTagName('channel')->item(0)->appendChild($node);
                    }
                }
            }

            $xml->save('output.xml');
        }

        exit();
    }

?>