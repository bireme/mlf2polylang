# mlf2polylang
Convert Multi-Language Framework to Polylang metadata

## Como utilizar

Antes de executar o script:
* Interromper a entrada de dados até o término de todo o processo de migração
* Realizar backup dos dados através da área administrativa em `Ferramentas > Exportar > Todo conteúdo`

No admin do WordPress:
* Ferramentas > Exportar > Download do arquivo de exportação do conteúdo desejado (em cada idioma)
* Renomear os arquivos seguindo o modelo `<nome_do_arquivo>.<sigla_do_idioma>.xml` (ex. wordpress.pt.xml)
* Apagar todos os conteúdos no WordPress que já foram exportados
* Desativar o plugin Multi-Language Framework
* Ativar e configurar o plugin Polylang com os mesmos idiomas que estavam ativos anteriormente

Na linha de comando, executar o script com o seguinte comando:

`php mlf2polylang.php <arquivo1>.xml <arquivo2>.xml ...`

No admin do WordPress:
* Ferramentas > Importar > Importar o arquivo result.xml

__OBS:__ Ao executar o script, o primeiro arquivo passado como parâmetro DEVE SER aquele que contém o idioma principal do site.