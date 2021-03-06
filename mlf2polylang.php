<?php

$help = "
/******************************************************************************************************

# COMO UTILIZAR #

Antes de executar o script:
* Interromper a entrada de dados até o término de todo o processo de migração
* Realizar backup dos dados através da área administrativa em Ferramentas > Exportar > Todo conteúdo

No admin do WordPress:
* Ferramentas > Exportar > Download do arquivo de exportação do conteúdo desejado (em cada idioma)
* Renomear os arquivos seguindo o modelo <nome_do_arquivo>.<sigla_do_idioma>.xml (ex. wordpress.pt.xml)
* Apagar todos os conteúdos no WordPress que já foram exportados
* Desativar o plugin Multilanguage Framework
* Ativar e configurar o plugin Polylang com os mesmos idiomas que estavam ativos anteriormente

Na linha de comando, executar o script com o seguinte comando:

    php mlf2polylang.php <arquivo1>.xml <arquivo2>.xml ...

No admin do WordPress:
* Ferramentas > Importar > Importar o arquivo result.xml

NOTA: Ao executar o script, o primeiro arquivo passado como parâmetro DEVE SER aquele que contém
      o idioma principal do site.

******************************************************************************************************/
\n";

require_once "i18n.php";
require_once "functions.php";

// read file path
$file = NULL;
if ( $argc > 1 ) {
    if ( in_array( $argv[1], array( '-m', 'merge' ) ) )
        merge_output_files( array_slice( $argv, 2 ) );
    else
        $file = $argv[1];
}
else {
    exit($help);
}

$label = '';
//$ids = array();
$lang = array();
$locale = array();
$post_translations = array();

// get language acronyms
foreach ($argv as $key => $value) {
    if ( $key > 0 ) {
        $parts = explode('.', $value);
        $index = (count($parts) - 2);
        $locale[] = $parts[$index];
        $lang[] = substr($parts[$index], 0, 2);
    }
}

foreach ($languages as $key => $value) {
    if ( $locale[0] == $value[1] ) {
        $label = $value[2];
        break;
    }
}

// Normalize main xml file with new elements for Polylang
$xml = new DOMDocument();
$xml->formatOutput = true;
$xml->preserveWhiteSpace = false;
$xml->load($file) or die("Error: Cannot create object");

$items = $xml->getElementsByTagName('item');

for ($i = 0; $i < $items->length; $i++) {
    $item = $items->item($i);
    $id = (int) $item->getElementsByTagName('post_id')->item(0)->nodeValue;
    //$ids[] = $id;
    $key = uniqid('pll_');
    $post_translations[$key] = array( $lang[0] => $id );

    $postmeta = $item->getElementsByTagName('postmeta');

    for ($j = 0; $j < $postmeta->length; $j++) {
        $meta_key = $postmeta->item($j)->getElementsByTagName('meta_key')->item(0);
        if ($meta_key->nodeValue == '_translation_of'){
            // create post_translations element
            $element = new DOMDocument();
            $element->formatOutput = true;
            $element->preserveWhiteSpace = false;
            $tag = $element->createElement('category');
            $attr = $element->createAttribute('domain');
            $attr->value = 'post_translations';
            $tag->appendChild($attr);
            $attr = $element->createAttribute('nicename');
            $attr->value = $key;
            $tag->appendChild($attr);
            $tag->appendChild($element->createCDATASection($key));
            $node = $xml->importNode($tag, true);
            $xml->getElementsByTagName('item')->item($i)->appendChild($node);
            break;
        }
    }

    // create language element
    $element = new DOMDocument();
    $element->formatOutput = true;
    $element->preserveWhiteSpace = false;
    $tag = $element->createElement('category');
    $attr = $element->createAttribute('domain');
    $attr->value = 'language';
    $tag->appendChild($attr);
    $attr = $element->createAttribute('nicename');
    $attr->value = $lang[0];
    $tag->appendChild($attr);
    $tag->appendChild($element->createCDATASection($label));
    $node = $xml->importNode($tag, true);
    $xml->getElementsByTagName('item')->item($i)->appendChild($node);
}

if ( $argc > 2 ) {
    // parse of other xml files
    foreach($argv as $index => $file) {
        if ( $index < 2 ) continue;

        $content = new DOMDocument();
        $content->formatOutput = true;
        $content->preserveWhiteSpace = false;
        $content->load($file) or die("Error: Cannot create object");

        $items = $content->getElementsByTagName('item');

        for ($i = 0; $i < $items->length; $i++) {
            $break = false;
            $item = $items->item($i);
            $id = (int) $item->getElementsByTagName('post_id')->item(0)->nodeValue;

            $postmeta = $item->getElementsByTagName('postmeta');

            for ($j = 0; $j < $postmeta->length; $j++) {
                $meta_key = $postmeta->item($j)->getElementsByTagName('meta_key')->item(0);
                if ($meta_key->nodeValue == '_translation_of'){
                    $meta_value = $postmeta->item($j)->getElementsByTagName('meta_value')->item(0);
                    if ( $meta_value->nodeValue ) {
                        foreach ($post_translations as $key => $value) {
                            if ( in_array($meta_value->nodeValue, $value) ){
                                $value[$lang[$index-1]] = $id;
                                $post_translations[$key] = $value;

                                // create post_translations element
                                $element = new DOMDocument();
                                $element->formatOutput = true;
                                $element->preserveWhiteSpace = false;
                                $tag = $element->createElement('category');
                                $attr = $element->createAttribute('domain');
                                $attr->value = 'post_translations';
                                $tag->appendChild($attr);
                                $attr = $element->createAttribute('nicename');
                                $attr->value = $key;
                                $tag->appendChild($attr);
                                $tag->appendChild($element->createCDATASection($key));
                                $node = $content->importNode($tag, true);
                                $content->getElementsByTagName('item')->item($i)->appendChild($node);

                                $break = true; break;
                            }
                        }
                    }
                }
                if ( $break ) break;
            }

            $label = '';
            foreach ($languages as $key => $value) {
                if ( $locale[$index-1] == $value[1] ) {
                    $label = $value[2];
                    break;
                }
            }

            // create language element
            $element = new DOMDocument();
            $element->formatOutput = true;
            $element->preserveWhiteSpace = false;
            $tag = $element->createElement('category');
            $attr = $element->createAttribute('domain');
            $attr->value = 'language';
            $tag->appendChild($attr);
            $attr = $element->createAttribute('nicename');
            $attr->value = $lang[$index-1];
            $tag->appendChild($attr);
            $tag->appendChild($element->createCDATASection($label));
            $node = $content->importNode($tag, true);
            $content->getElementsByTagName('item')->item($i)->appendChild($node);

            // import/copy item from other documents to main document
            $main_item = $xml->importNode($item, true);

            // append imported item to main document 'channel' element
            $xml->getElementsByTagName('channel')->item(0)->appendChild($main_item);
        }
    }

    if ( $post_translations ) {
        foreach ($post_translations as $key => $value) {
            $children = array(
                'taxonomy' => 'post_translations',
                'slug' => $key,
                'name' => $key,
                'description' => serialize($post_translations[$key]),
            );

            $element = new DOMDocument();
            $element->formatOutput = true;
            $element->preserveWhiteSpace = false;
            $tag = $element->createElement('wp:term');

            foreach ($children as $k => $v) {
                $child = $element->createElement('wp:term_'.$k);
                $child->appendChild($element->createCDATASection($v));
                $tag->appendChild($child);
            }

            $node = $xml->importNode($tag, true);
            $xml->getElementsByTagName('channel')->item(0)->appendChild($node);
        }
    }
}

// create language and term_language elements
if ( $locale ) {
    foreach ($locale as $key => $value) {
        // language element
        $label = $locale_description[$value]['label'];
        unset($locale_description[$value]['label']);

        $children = array(
            'taxonomy' => 'language',
            'slug' => substr($value, 0, 2),
            'name' => $label,
            'description' => serialize($locale_description[$value]),
        );

        $element = new DOMDocument();
        $element->formatOutput = true;
        $element->preserveWhiteSpace = false;
        $tag = $element->createElement('wp:term');

        foreach ($children as $k => $v) {
            $child = $element->createElement('wp:term_'.$k);
            $child->appendChild($element->createCDATASection($v));
            $tag->appendChild($child);
        }

        $node = $xml->importNode($tag, true);
        $xml->getElementsByTagName('channel')->item(0)->appendChild($node);

        // term_language element
        $children = array(
            'taxonomy' => 'term_language',
            'slug' => 'pll_'.substr($value, 0, 2),
            'name' => $label
        );

        $element = new DOMDocument();
        $element->formatOutput = true;
        $element->preserveWhiteSpace = false;
        $tag = $element->createElement('wp:term');

        foreach ($children as $k => $v) {
            $child = $element->createElement('wp:term_'.$k);
            $child->appendChild($element->createCDATASection($v));
            $tag->appendChild($child);
        }

        $node = $xml->importNode($tag, true);
        $xml->getElementsByTagName('channel')->item(0)->appendChild($node);
    }
}

$xml->save('result.xml');
remove_mlf_suffix('result.xml', $lang);