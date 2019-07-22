<?php

// @autor - wanderlei santana
// @email - sans.pds@gmail.com
// @data  - 2016.02.06 021:29 PM

header( 'Content-Type: text/html; charset=utf-8' );

require 'cls_session.class.php';
$session = new Cls_session();

// definicao de algumas constantes ...
// regras para pargura minima e maxima...
define( "MIN_LARGURA", 11 );
define( "MAX_LARGURA", 105 );

// regras para altura minima e maxima...
define( "MIN_ALTURA", 2 );
define( "MAX_ALTURA", 105 );

// regras para comprimento minimo e maximo...
define( "MIN_COMPRIMENTO", 16 );
define( "MAX_COMPRIMENTO", 105 );

// regras para soma de comprimento + largura + altura minimo e maximo...
define( "MIN_SOMA_CLA", 29 );
define( "MAX_SOMA_CLA", 200 );


/**
 * Ordena itens dentro do carrinho, de forma que sua maior dimensao
 * seja comprimento, menor dimensao altura e por fim largura como
 * a dimensao com valor intermediario.
 * Tambem ordena os itens de forma que o maiores estejam ao topo do array
 *
 * @param  array  $_cart_ - carrinho contendo itens e dimensoes
 * @return array          - carrinho ordenado
 */
function orderCart( $_cart_ = null )
{

    if(!is_array($_cart_)) return $_cart_ ;


    // percorre itens reatribuindo suas dimensoes...
	foreach ($_cart_ as $k => $_item_):
		// passo 1 - a menor dimensao do nosso produto sera nossa altura ...
		$__new_alt__ = min( $_item_['A'], $_item_['L'], $_item_['C'] ) ;

		// passo 1.1 - o maior valor sera o comprimento ...
		$__new_comp__ = max( $_item_['A'], $_item_['L'], $_item_['C'] ) ;

		// passo 1.2 - o valor do meio sera a largura...
		$_tmp_ = array( $_item_['A'], $_item_['L'], $_item_['C'] ) ;
		sort( $_tmp_ ) ;
		array_shift($_tmp_) ;
		array_pop($_tmp_) ;

		$_item_['L'] = isset($_tmp_[0])? $_tmp_[0] : $__new_alt__;
		$_item_['A'] = $__new_alt__ ;
		$_item_['C'] = $__new_comp__ ;

		$_item_['LC'] = $_item_['L'] * $_item_['C'] ;

		$_cart_[$k] = $_item_ ;

	endforeach;

	// ordena array
	function ordenarPorLC($a, $b) {
    	return $a['LC'] < $b['LC'];
	}

	usort($_cart_, 'ordenarPorLC');

	return $_cart_ ;
}

/**
 * [calcBox description]
 * @param  array  $_cart_ [description]
 * @return object        [description]
 */
function calcBox( $_carrinho_ = null )
{
	global $session;

	if(!is_array($_carrinho_)) return $_carrinho_;

	$_carrinho_ = orderCart( $_carrinho_ );

	// @param array - guarda informacoes acerca da caixa a utilizar
	$_box_ = array(
		'altura' => 0, 		 /* altura final da caixa */
		'largura' => 0, 	 /* largura */
		'comprimento' => 0,  /* ... */
		'qtd_itens' => 0, 	 /* qtd de itens dentro da caixa */
		'message' => null,   /* caso erro guarda mensagem */
		'volume' => 0, 		 /* capacidade total de armazenamento da caixa */
		'volume_itens' => 0, /* volume armazenado */
		'volume_vazio' => 0, /* volume livre */
		'comprimento_remanescente' => 0,
		'largura_remanescente' => 0,
		'altura_remanescente' => 0
	);

	$box = json_decode(json_encode($_box_,FALSE));

	// checando se carrinho nao esta vazio ...
	if( empty($_carrinho_) )
		die('Erro : Carrinho encontra-se vazio.') ;

	// percorrendo lista de produtos realizando calculos devidos ...
	foreach ($_carrinho_ as $_item_):

		// incrementa quantidade de itens dentro da caixa...
		$box->qtd_itens++;

        // opcional para gerar imagem dinamica apenas...
        $__ = array(
            'title' => $_item_['title'],
            'h' => $_item_['A'],
            'l' => $_item_['L'],
            'z' => $_item_['C'],
            'side' => false,
        );

		// @opcional - calculando volume de itens dentro da caixa ...
		$box->volume_itens += ( $_item_['A']*$_item_['L']*$_item_['C'] ) ;

		// verifica se produto cabe no espaco remanescente ...
		if( $box->comprimento_remanescente >= $_item_['C'] &&
			$box->largura_remanescente >= $_item_['L']):
			// se altura do novo produto maior que altura disponivel, incrementa altura da caixa...
			if( $_item_['A'] > $box->altura_remanescente){
				$box->altura += $_item_['A'] - $box->altura_remanescente ;
			}

			if ( $_item_['C'] > $box->comprimento )
				$box->comprimento = $_item_['C'] ;

			// calculando volume remanescente do valor remanescente!!!
			$box->comprimento_remanescente = $box->comprimento - $_item_['C'];

			// largura restante
			$box->largura_remanescente = $box->largura_remanescente - $_item_['L'] ;

			$box->altura_remanescente = $_item_['A'] > $box->altura_remanescente ?
				$_item_['A'] : $box->altura_remanescente ;
            $__['side'] = true;
            $_itens_[] = $__ ;
			// pula para proxima iteracao...
			continue ;
		endif;

		// passo (N-1) - altura e' a variavel que sempre incrementa independente de condicao ...
		$box->altura += $_item_['A'] ;

		// passo N - verificando se item tem dimensoes maiores que a caixa...
		if ( $_item_['L'] > $box->largura )
			$box->largura = $_item_['L'] ;

		if ( $_item_['C'] > $box->comprimento )
			$box->comprimento = $_item_['C'] ;

		// calculando volume remanescente...
		$box->comprimento_remanescente = $box->comprimento ;
		$box->largura_remanescente = $box->largura - $_item_['L'] ;
		$box->altura_remanescente = $_item_['A'] ;

    $_itens_[] = $__ ;

	endforeach;

	// @opcional - calculando volume da caixa ...
	$box->volume = ( $box->altura*$box->largura*$box->comprimento ) ;

	// @opcional - calculando volume vazio! Ar dentro da caixa!
	$box->volume_vazio = $box->volume - $box->volume_itens ;

	// checa se temos produtos e se conseguimos alcancar a dimensao minima ...
	if( !empty( $_carrinho_ ) ):
		// verificando se dimensoes minimas sao alcancadas ...
		if( $box->altura > 0 && $box->altura < MIN_ALTURA ) $box->altura = MIN_ALTURA ;
		if( $box->largura > 0 && $box->largura < MIN_LARGURA ) $box->largura = MIN_LARGURA ;
		if( $box->comprimento > 0 && $box->comprimento < MIN_COMPRIMENTO ) $box->comprimento = MIN_COMPRIMENTO ;
	endif;

	// verifica se as dimensoes nao ultrapassam valor maximo
	if( $box->altura > MAX_ALTURA ) $box->message = "Erro: Altura maior que o permitido.";
	if( $box->largura > MAX_LARGURA ) $box->message = "Erro: Largura maior que o permitido.";
	if( $box->comprimento > MAX_COMPRIMENTO ) $box->message = "Erro: Comprimento maior que o permitido.";

	// @nota - nao sei se e' uma regra, mas por via das duvidas esta ai
	// Soma (C+L+A)	MIN 29 cm  e  MAX 200 cm
	if( ($box->comprimento+$box->comprimento+$box->comprimento) < MIN_SOMA_CLA )
		$box->message = "Erro: Soma dos valores C+L+A menor que o permitido.";

	if( ($box->comprimento+$box->comprimento+$box->comprimento) > MAX_SOMA_CLA )
		$box->message = "Erro: Soma dos valores C+L+A maior que o permitido.";

    $session->items = $_itens_;

	return $box;
}
// <!-- /fim function -->



// @param array - lista de produtos hipotetica ...
/*$_cart_ = array(
	array( 'title' => 'Livro - A Arte da Guerra',		  'A' => 18, 'L' => 13, 'C' => 4 ),
	array( 'title' => 'Livro - Use a Cabeça Estatistica', 'A' => 21, 'L' => 14, 'C' => 5 ),
	array( 'title' => 'Livro - Use a Cabeça Web Design',  'A' => 21, 'L' => 14, 'C' => 5 ),
	array( 'title' => 'Perfume Boticário Egeo', 		  'A' => 23, 'L' => 8,  'C' => 8 ),
	// array( 'title' => 'Perfume Natura Kaiak', 		  	  'A' => 18, 'L' => 4,  'C' => 9 ),
);*/



$box = calcBox( $_cart_ );



// resultado final
echo "<div class='infos'>
	<br /><b> Dimensções da Caixa </b> <br />
	  Altura          : {$box->altura} cm, <br />
	  Largura         : {$box->largura} cm, <br />
	  Comprimento     : {$box->comprimento} cm, <br />
	  Itens 		  :	{$box->qtd_itens} un, <br />
	  Volume          : {$box->volume} cm2, <br />
	  Volume Produtos : {$box->volume_itens} cm2, <br />
	  Volume Vazio    : {$box->volume_vazio} cm2, <br />
	  </div>" ;

        echo ( is_null( $box->message ) ) ? "" : $box->message ;


        $session->altura 		= $box->altura ;
        $session->largura 		= $box->largura ;
        $session->comprimento 	= $box->comprimento ;
        $session->qtd_itens 	= $box->qtd_itens ;
        $session->volume 		= $box->volume ;
        $session->volume_itens 	= $box->volume_itens ;
        $session->volume_vazio 	= $box->volume_vazio ;


?>

<style>
body{margin:0;font-family:'Open Sans',sans-serif;color:#fff;}
h1{position: absolute;font-size: 25px;left:250px;font-weight: 100;top: -4px;}
.infos{padding:10px;position:absolute;color:#fff;top:-10px;left:10px;font-weight: 200;}
</style>

<h1>Encomenda a ser enviada via SEDEX</h1>
<img src="image.php" />