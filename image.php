<?php

    require 'cls_session.class.php' ; 

    $session = new cls_session;

    /**
     * [create3DBox description]
     * @param  [type] &$im     imagem a ser implantada a caixa
     * @param  array  $_param_ [x(int),y(int),w(int),h(int),z(int),border(int), color(R,G,B), fill(bool), alpha(int:0-127)]
     * @return [type]          [description]
     */
    function create3DBox(&$im , $c = array() )
    {
        if(!function_exists('makePolygon'))
        {
            function makePolygon(&$im, $points,$color)
            {
                global $o;
                if(isset($o->fill) && $o->fill == true):
                    imagefilledpolygon($im, $points, 4, $color );
                else:
                    imagepolygon($im, $points, 4,$color );
                endif;
            }
        }

        $font = 'Aileron-Regular.otf';

        // para corrigir questao estetica, diminui eixo Z pela metade 
        $__real_z_axis__ = $c['z'] ;
        $c['z'] = $c['z']*0.5;

        $o = json_decode(json_encode($c,FALSE));

        // setar transparencia ?
        if(!isset($o -> alpha)) $o -> alpha = 0;

        if(isset($o->border)&&$o->border>0)
            imagesetthickness( $im , $o->border );

        // decidindo cor
        if(!empty($o->color))
            $color = imagecolorallocatealpha($im, $o->color[0], $o->color[1], $o->color[2], $o->alpha );
        else
            $color = imagecolorallocatealpha($im, 15, 142, 210, 0);

        // mapeando as coodenadas da nossa caixa para
        // facilitar o trabalho
        $vertices = array
        (
            'a' => array( 'x' => $o->x, 'y' => $o->y ),
            'b' => array( 'x' => $o->x, 'y' => $o->y + $o->h ),
            'c' => array( 'x' => $o->x + $o->w, 'y' => $o->y + $o->h ),
            'd' => array( 'x' => $o->x + $o->w, 'y' => $o->y ),
            'e' => array( 'x' => $o->x + $o->z, 'y' => $o->y-$o->z ),
            'f' => array( 'x' => $o->x + $o->z, 'y' => $o->y-$o->z + $o->h ),
            'g' => array( 'x' => $o->x + $o->z + $o->w, 'y' => $o->y-$o->z+$o->h ),
            'h' => array( 'x' => $o->x + $o->z + $o->w, 'y' => $o->y-$o->z),
        );

        $vertices = json_decode(json_encode($vertices,FALSE));

        $points = array(
            $vertices->a->x, $vertices->a->y,
            $vertices->b->x, $vertices->b->y,
            $vertices->c->x, $vertices->c->y,
            $vertices->d->x, $vertices->d->y,
        );

        makePolygon($im,$points,$color);

        // .......... 3D ............
        if(isset($o->z) && $o->z > 0 ):

            // ......... quadrado fundo ...
            $points = array(
                $vertices->e->x, $vertices->e->y,
                $vertices->f->x, $vertices->f->y,
                $vertices->g->x, $vertices->g->y,
                $vertices->h->x, $vertices->h->y,
            );

            makePolygon($im,$points,$color);

            // ......... quadrado topo ...
            $points = array(
                $vertices->e->x, $vertices->e->y,
                $vertices->a->x, $vertices->a->y,
                $vertices->d->x, $vertices->d->y,
                $vertices->h->x, $vertices->h->y,
            );

            makePolygon($im,$points,$color);

            // ......... quadrado baixo ...
            $points = array(
                $vertices->f->x, $vertices->f->y,
                $vertices->b->x, $vertices->b->y,
                $vertices->c->x, $vertices->c->y,
                $vertices->g->x, $vertices->g->y,
            );

            makePolygon($im,$points,$color);

            if(isset($o->destaque)&&$o->destaque==true):
                $blue_light = imagecolorallocatealpha($im, 155, 206, 255, 0);
                imageline($im, $vertices->e->x, $vertices->e->y, $vertices->f->x, $vertices->f->y, $blue_light);
                imageline($im, $vertices->f->x, $vertices->f->y, $vertices->b->x, $vertices->b->y, $blue_light);
                imageline($im, $vertices->f->x, $vertices->f->y, $vertices->g->x, $vertices->g->y, $blue_light);
            endif;

            // ................................ ABAS ...........................................
            // desenhar aba
            if(isset($o->abas) && $o->abas == TRUE ):

                // criando novos pontos
                $n_pontos = array(
                    // aba baixo
                    'i' => array( 'x' => $vertices->a->x - ($o->z/3)-30, 'y' => $vertices->a->y + ($o->z/3) ),
                    'j' => array( 'x' => $vertices->d->x - ($o->z/3)-30, 'y' => $vertices->d->y + ($o->z/3) ),
                    // aba topo
                    'k' => array( 'x' => $vertices->e->x - ($o->z/3)+10, 'y' => $vertices->e->y - ($o->z/3) ),
                    'l' => array( 'x' => $vertices->h->x - ($o->z/3)-10, 'y' => $vertices->h->y - ($o->z/3) ),
                    // aba esquerda
                    'm' => array( 'x' => $vertices->e->x - ($o->w/4), 'y' => $vertices->e->y - ($o->w/4) ),
                    'n' => array( 'x' => $vertices->a->x - ($o->w/4), 'y' => $vertices->a->y - ($o->w/4) ),
                    // aba direita
                    'o' => array( 'x' => $vertices->d->x - ($o->w/4)+10, 'y' => $vertices->d->y - ($o->w/4) ),
                    'p' => array( 'x' => $vertices->h->x - ($o->w/4)+10, 'y' => $vertices->h->y - ($o->w/4) ),
                );

                $abas = json_decode(json_encode($n_pontos, FALSE));

                // aba baixo .........
                $points = array(
                    $vertices->a->x, $vertices->a->y,
                    $abas->i->x, $abas->i->y,
                    $abas->j->x, $abas->j->y,
                    $vertices->d->x, $vertices->d->y,
                );

                makePolygon($im,$points,$color);

                // aba topo .........
                $points = array(
                    $abas->k->x, $abas->k->y,
                    $vertices->e->x, $vertices->e->y,
                    $vertices->h->x, $vertices->h->y,
                    $abas->l->x, $abas->l->y,
                );

                makePolygon($im,$points,$color);

                // aba esquerda .........
                $points = array(
                    $abas->m->x, $abas->m->y,
                    $abas->n->x, $abas->n->y,
                    $vertices->a->x, $vertices->a->y,
                    $vertices->e->x, $vertices->e->y,
                );

                makePolygon($im,$points,$color);

                // aba direita .........
                $points = array(
                    $vertices->h->x, $vertices->h->y,
                    $vertices->d->x, $vertices->d->y,
                    $abas->o->x, $abas->o->y,
                    $abas->p->x, $abas->p->y,
                );

                makePolygon($im,$points,$color);

                // verificando se devemos apresentar informacoes sobre
                // dimensoes da caixa
                if(isset($o->show_info)&&$o->show_info == TRUE ):

                    // informando sobre a altura ...
                    $margin = 10 ;
                    $hr = 6;
                    $font_size = 11;
                    imageline ($im, $vertices->h->x + $margin, $vertices->h->y, $vertices->h->x + $margin + $hr, $vertices->h->y , $color ) ;
                    imageline ($im, $vertices->g->x + $margin, $vertices->g->y-$hr, $vertices->g->x + $margin + $hr, $vertices->g->y-$hr , $color ) ;
                    imageline ($im, $vertices->h->x + $margin + ($hr/2) , $vertices->h->y , $vertices->g->x + $margin + ($hr/2) , $vertices->g->y -$hr , $color ) ;
                    imagettftext($im, $font_size/*size*/, 0, $vertices->h->x + ($margin*2) + $hr /*X*/, $vertices->h->y+($o->h/2)/*Y*/, $color/*Color*/, $font, "Altura: ".($o->h/10)." cm");

                    // informacoes sobre a comprimento ...
                    imageline ($im, $vertices->g->x + $margin, $vertices->g->y, $vertices->g->x + $margin + $hr, $vertices->g->y , $color ) ;
                    imageline ($im, $vertices->c->x + $margin, $vertices->c->y, $vertices->c->x + $margin + $hr, $vertices->c->y , $color ) ;
                    imageline ($im, $vertices->g->x + $margin + ($hr/2) , $vertices->g->y , $vertices->c->x + $margin + ($hr/2) , $vertices->c->y , $color ) ;
                    imagettftext( $im, $font_size/*size*/, 0, ($vertices->g->x+($margin*3)+$hr)-($o->z/2) /*X*/, $vertices->g->y+($o->z/2)/*Y*/, $color/*Color*/, $font, "Comp.: ".($__real_z_axis__/10)." cm");

                    imageline ($im, $vertices->b->x, $vertices->b->y + $margin, $vertices->b->x, $vertices->b->y + $margin + $hr, $color ) ;
                    imageline ($im, $vertices->c->x, $vertices->c->y + $margin, $vertices->c->x, $vertices->c->y + $margin + $hr, $color ) ;
                    imageline ($im, $vertices->b->x, $vertices->b->y + $margin + ($hr/2), $vertices->c->x, $vertices->c->y + $margin + ($hr/2), $color ) ;
                    imagettftext($im, $font_size/*size*/, 0, ($vertices->b->x+($o->w/2))-50 /*X*/, $vertices->b->y+($margin*3)+$hr/*Y*/, $color/*Color*/, $font, "Largura: ".($o->w/10)." cm");

                endif;

                // escreve alguns adornos ....
                $altura = floor(($o->w/4)/4);
                $largura= floor($o->z -($o->z/5));
                $size   = (($altura*4)>$largura) ? floor($largura/4) : $altura;
                imagettftext($im,$size/*size*/, 45,$vertices->d->x /*X*/,$abas->o->y+15 /*Y*/,$color /*Color*/, $font, "correios");
                
                $altura = floor($o->h/4);
                $largura= floor($o->w-($o->w/5));
                $size   = (($altura*4)>$largura) ? floor($largura/4) : $altura;
                imagettftext($im,$size/*size*/, 0,($vertices->a->x + ($o->w/2) - ($size*4)/2) /*X*/,($vertices->a->y + ($o->h/2) + ($size/2) )/*Y*/,$color /*Color*/, $font, "SEDEX");

            endif;

        endif;

    }



    // ....................................................................
    $__imgw__ = 800 ;
    $__imgh__ = 600 ;

    // primeiro pixel plotado na imagem que e' gerada dinamicamente
    $__ponto_inicial_x__ = 210 ;
    $__ponto_inicial_y__ = 300 ;

    // cria a imagem
    $im = imagecreatetruecolor($__imgw__, $__imgh__);
    // setando background para branco
    $bg   = imagecolorallocate($im, 15, 142, 210);
    $blue = imagecolorallocate($im, 15, 142, 210);
    imagefilledrectangle( $im,0 ,0 ,$__imgw__-1,$__imgh__-1,$bg );
    imageantialias( $im, TRUE );

    // configuracoes de fonte
    $font = 'Aileron-Regular.otf';

$ix = 0 ; 
$iy = $__ponto_inicial_y__ + ($session->altura*10) ;
$lw = 0 ; 
$__idx__ = 0;
$_cores_ = array(
    array(254, 121, 21),
    array(223, 23, 1),
    array(148, 15, 210),
    array(33, 33, 33),
    array(214, 223, 1),
    array(12, 177, 49),
    array(41, 11, 176),
    array(215, 142, 10),
    array(41, 111, 176),
);

foreach($session->items as $item):

    if($item['side']):
        $ix = $__ponto_inicial_x__ + $lw; 
    else:
        $ix = $__ponto_inicial_x__;
        $iy = $iy - ($item['h']*10)-1;
    endif;

    $lw = $item['l']*10;

    $__idx__ = $__idx__ < 7 ? $__idx__ : 0;
    $__idx__++;
    $_rcolor_ = $_cores_[$__idx__] ;

    create3DBox( $im,
        array(
            'x' => $ix ,
            'y' => $iy ,
            'w' => $lw ,
            'h' => $item['h'] * 10,
            'z' => $item['z'] * 10,
            'color' => $_rcolor_,
            'alpha' => 0
        )
    );

endforeach;

    // create3DBox( $im,
    //     array(
    //         'x' => 202,
    //         'y' => 368,
    //         'w' => 128,
    //         'h' => 50,
    //         'z' => 98,
    //         'color' => array(254, 121, 21),
    //         'alpha' => 0
    //     )
    // );

    // create3DBox( $im,
    //     array(
    //         'x' => 202,
    //         'y' => 316,
    //         'w' => 108,
    //         'h' => 50,
    //         'z' => 98,
    //         'color' => array(148, 15, 210),
    //         'alpha' => 0
    //     )
    // );

    // create3DBox( $im,
    //     array(
    //         'x' => 202,
    //         'y' => 216,
    //         'w' => 108,
    //         'h' => 100,
    //         'z' => 68,
    //         'color' => array(223, 23, 1),
    //         'alpha' => 0
    //     )
    // );

    create3DBox( $im,
        array(
            'x' => $__ponto_inicial_x__,
            'y' => $__ponto_inicial_y__,
            'w' => $session->largura*10, /* multiplica apenas por questao estetica */
            'h' => $session->altura*10, /* multiplica apenas por questao estetica */
            'z' => $session->comprimento*10, /* multiplica apenas por questao estetica */
            'fill' => false,
            'abas' => true,
            'show_info' => true,
            'destaque' => true,
            'color' => array(255, 255, 255),
            'alpha' => 0
        )
    );



    // create3DBox( $im,
    //     array(
    //         'x' => 455,
    //         'y' => 150,
    //         'w' => 150,
    //         'h' => 150,
    //         'z' => 30,
    //         'fill' => false,
    //         'color' => array(215, 142, 10),
    //         'alpha' => 0
    //     )
    // );

    header('Content-type: image/png');
    imagepng($im);
    imagedestroy($im);
