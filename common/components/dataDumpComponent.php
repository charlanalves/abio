<?php

namespace common\components;

use Yii;
use yii\base\Component;

/**
* dataDumpComponent
* Classe responsavel por retornar xml
*
* @access Public
* @author Vitor Hallais
* @package Component
* @since  05/2016
* @example Seguranca/usuario/xmldados
*
**/
class dataDumpComponent extends Component
{

    public function getXML($dados=null,$config=null)
    {
		$novaLinhaCol = $attachHeader = null;
        $numColuna = array();
        $ordem = array();
        $novo = array();
        $cont = 0;
        $xml  = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $xml .= "<rows>\n";

        if (is_array($config)) {
            if (!array_key_exists('imagem',$config)) $config['imagem'] = null;
            foreach($config as $k=>$cabecalho) {
                if ($k == 'header') {
                    $xml .= "<head>\n";
                    foreach($cabecalho as $k2=>$colunas) {
                        foreach($colunas as $k3=>$coluna) {
                            if ($k2 == 0) { // Primeira linha do Cabecalho
                                $valor_coluna = '';
                                $xml .= "		<column ";
                                foreach($coluna as $param=>$col) {
                                    if ($param == 'title') {
                                        $valor_coluna .= $col;
                                    } else if (is_array($col)) {
                                        foreach($col as $k=>$v) {
                                            $valor_coluna .= "<".$param;
                                            foreach($v as $propriedade=>$val_prop) {
                                                if ($propriedade != 'text') {
                                                    $valor_coluna .= " $propriedade='$val_prop'";
                                                } else {
                                                    $valorOption = $val_prop;
                                                }
                                            }
                                            $valor_coluna .= ">".$valorOption."</".$param.">";
                                        }
                                    } else {
                                        $xml .= $param."='".$col."' ";
                                        // verifica e guarda o numero da coluna caso a coluna seja de imagem (tratamento diferenciado para imprimir a imagem)
                                        if ($param == 'type' and $col == 'img') {
                                            $numColuna[] = $cont;
                                        }

                                        if ($param == 'id') {
                                            $ordem[] = $col;
                                        }
                                    }
                                }
                                $xml .= ">".$valor_coluna."</column>";
                                $cont++;
                                unset($valor_coluna,$valorOption);
                            } else { // outras linhas do cabecalho
                                foreach($coluna as $param=>$col) {
                                    $novaLinhaCol[$k2][] = $col;
                                }
                            }
                        }
                    }
					// Outras linhas do cabecalho
                    if (is_array($novaLinhaCol)) {
                        $attachHeader .= "<afterInit>";
						foreach($novaLinhaCol as $colunas) {
							$inicioAttach = false;
							$attachHeader .= '   <call command="attachHeader"> <param>';
							foreach($colunas as $col) {
								if ($inicioAttach) $separador = ",";
								else {
									$separador = "";
									$inicioAttach = true;
								}
								$attachHeader .= $separador.$col;
							}
							$attachHeader .= "</param></call>";
						}
                        $attachHeader .= "</afterInit>\n";
                        $xml .= $attachHeader;
                    }
                    $xml .= "</head>\n";
                }
            }
        }
        // varre os array de dados para imprimir o xml na forma correta
        if (is_array($dados)) {

            foreach($dados as $k0 => $v) {
                $linha = '';
                $xml .= "<row ";
                $contCols = 0; // zera o contador de colunas a cada nova linha de registros

               if (!empty($ordem)) {
                    foreach ($ordem as $o) {
                       $novo[$o] = $v[$o];

                    }

                    if (isset($v['ID'])) $novo['ID'] = $v['ID'];
               } else {
                   $novo = $v;
               }


               foreach($novo as $k=> $valores) {
                    // seta o ID da row caso seja passado
                    if ($k === 'ID') $xml .= "id='".$valores."'";
                    // verifica se a coluna Ã© do tipo imagem para dar tratamento diferenciado.
                    else if (in_array($contCols,$numColuna)) $linha .= "<cell>".$valores."</cell>\n";
                    // outro modo de setar o campo como imagem para ter tratamento diferenciado
                    // -> usado normalmente quando nao se tem header no XML e precisa definir o campo como imagem.
                    else if (is_array($config['imagem']) and in_array($k,$config['imagem'])) $linha .= "<cell>".$valores."</cell>\n";
                    // todos os outros tipos serÃ£o encapsolados por CDATA
                    else $linha .= "<cell><![CDATA[".$valores."]]></cell>\n";
                    $contCols++;
                }
                $xml .= ">".$linha."</row>\n";
            }
        }
        $xml .= "</rows>";
        return $xml;
    }


	public function getXmlCombo($data = [], $emptyText = 'Selecione...', $selecionado='')
    {
        if (empty($data)) {
            $data = [];
        }

        $options = '';

        $rootTag = 'complete';

        $startXml = '<?xml version="1.0" encoding="utf-8"?>';
        $bodyXml = "<$rootTag>";

        if (!empty($data)) {
            if ($emptyText !== false) {
                $options = '<option value="" selected="true">'.Yii::t("app", $emptyText).'</option>';
            }

            foreach ($data as $id => $text) {
                if($id==$selecionado)
                  $options .= '<option value="'.$id.'" selected="1">'.$text.'</option>';
                else
                  $options .= '<option value="'.$id.'">'.$text.'</option>';
            }
        }

        $bodyXml .= $options;
        $endXml = "</$rootTag>";

        $xml = $startXml . $bodyXml . $endXml;

        return $xml;
    }

    /**
     *  getXmlTreeview
     *  Recebe array e retorna xml no formato para treeview do dhtmlx
     *
     *  @author Vitor Hallais
     *  @return string
     *
     */
    public function getXmlTreeview($data = [])
    {
        if (empty($data)) {
            $data = [];
        }

        $options = '';
        $temAnterior = false;

        $startXml = '<?xml version="1.0" encoding="utf-8"?>';
        $bodyXml = '<tree id="0">';

        if (!empty($data))
            $options .= Yii::$app->dataDumpComponent->xmlRecursivoTreeView($data);

        $bodyXml .= $options;
        $endXml = "</tree>";

        $xml = $startXml . $bodyXml . $endXml;

        return $xml;
    }

    /**
     *  xmlRecursivoTreeView
     *  metodo recursivo para montar a estrutura do xml da treeview
     *
     *  @author Vitor Hallais
     *  @return string
     *
     */
    public function xmlRecursivoTreeView($dadosTree)
    {
        if (!is_array($dadosTree)) return false;
        $options = '';
        foreach($dadosTree as $k=>$v) {
            $options .= '<item ';
            if (is_array($v)) {
                foreach($v as $k2=>$v2) {
                    if ($k2 != 'opcoes') {
                        $options .= $k2.'="'.$v2.'" ';
                    }
                }
                if (array_key_exists('opcoes',$v) and is_array($v['opcoes'])) {
                    $options .= '>';
                    $options .= Yii::$app->dataDumpComponent->xmlRecursivoTreeView($v['opcoes']);
                    $options .= '</item>';
                } else {
                    $options .= '/>';
                }
            } else {
                $options .= '/>';
            }
        }
        return $options;
    }
}
?>
