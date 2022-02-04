<?php

use Adianti\Control\TPage;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Util\TXMLBreadCrumb;

class ContasDashboard extends TPage
{
    function __construct()
    
    {
        parent::__construct();

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';

        $div = new TElement('div');
        $div->class = "row";

        try
        {
            TTransaction::open('db_condominio');

            $valor      = ContaPagar::where('valor', '>', 0)->sumBy('valor');
            $valor_pago = ContaPagar::where('valor_pago', '>', 0)->sumBy('valor_pago');
            $juros      = ContaPagar::where('saldo', '>', 0)->sumBy('saldo');

            $valor_receber  = ContaReceber::where('valor', '>', 0)->sumBy('valor');
            $valor_recebido = ContaReceber::where('valor_recebido', '>', 0)->sumBy('valor_recebido');
            $juros_recebido = ContaReceber::where('juros_recebido', '>', 0)->sumBy('juros_recebido');

            TTransaction::close();

            $indicador1 = new THtmlRenderer('app/resources/info-box.html');
            $indicador2 = new THtmlRenderer('app/resources/info-box.html');
            $indicador3 = new THtmlRenderer('app/resources/info-box.html');
            $indicador4 = new THtmlRenderer('app/resources/info-box.html');
            $indicador5 = new THtmlRenderer('app/resources/info-box.html');
            $indicador6 = new THtmlRenderer('app/resources/info-box.html');

            $indicador1->enableSection('main', ['title' => 'VALOR A PAGAR', 'icon' => 'money-bill', 'background' => 'blue',
                                                'value' => 'R$ ' . number_format($valor, 2,',','.') ] );
                                                
            $indicador2->enableSection('main', ['title' => 'TOTAL A PAGAR', 'icon' => 'money-bill', 'background' => 'green',
                                                'value' => 'R$ ' . number_format($valor_pago, 2,',','.') ] );

            $indicador3->enableSection('main', ['title' => 'JUROS', 'icon' => 'money-bill', 'background' => 'green',
                                                'value' => 'R$ ' . number_format($juros, 2,',','.') ] );

            $indicador4->enableSection('main', ['title' => 'VALOR A RECEBER', 'icon' => 'money-bill', 'background' => 'red',
                                                'value' => 'R$ '.number_format($valor_receber, 2,',','.') ] );

            $indicador5->enableSection('main', ['title' => 'VALOR RECEBIDO', 'icon' => 'money-bill', 'background' => 'pink',
                                                'value' => 'R$ '.number_format($valor_recebido, 2,',','.') ] );

            $indicador6->enableSection('main', ['title' => 'JUROS RECEBIDO', 'icon' => 'money-bill', 'background' => 'orange',
                                                'value' => 'R$ '.number_format($juros_recebido, 2,',','.') ] );

            $div->add( TElement::tag('div', $indicador1, ['class' => 'col-sm-6']) );
            $div->add( TElement::tag('div', $indicador2, ['class' => 'col-sm-6']) );
            $div->add( TElement::tag('div', $indicador3, ['class' => 'col-sm-6']) );
            $div->add( TElement::tag('div', $indicador4, ['class' => 'col-sm-6']) );
            $div->add( TElement::tag('div', $indicador5, ['class' => 'col-sm-6']) );
            $div->add( TElement::tag('div', $indicador6, ['class' => 'col-sm-6']) );

            $table1 = TTable::create( [ 'class' => 'table table-striped table-hover', 'style' => 'border-collapse:collapse'] );
            $table1->addSection('thead');
            $table1->addRowSet('', 'Valor', 'Valor Pago', 'Juros');

            if($valor)
            {
                $table1->addSection('tbody');

                $table1->addSection('tfoot')->style = 'color:blue';
                $row = $table1->addRow();
                $row->addCell('Total');
                $row->addCell('R$ ' . number_format($valor, 2,',','.'))->style = 'text-align:left';
                $row->addCell('R$ ' . number_format($valor_pago, 2,',','.'))->style = 'text-align:left';
                $row->addCell('R$ '. number_format($juros, 2,',','.'))->style = 'text-align:left';
            }
            $div->add( TElement::tag('div', TPanelGroup::pack('Valor Total a Pagar', $table1), ['class' => 'col-sm-6']));
        
            $table2 = TTable::create( [ 'class' => 'table table-striped table-hover', 'style' => 'border-collapse:collapse'] );
            $table2->addSection('thead');
            $table2->addRowSet('', 'Valor Receber', 'Valor Recebido', 'Juros Recebido');

            if($valor_receber)
            {
                $table2->addSection('tbody');

                $table2->addSection('tfoot')->style = 'color:blue';
                $row = $table2->addRow();
                $row->addCell('Total Receber');
                $row->addCell('R$ ' . number_format($valor_receber, 2,',','.'))->style = 'text-align:left';
                $row->addCell('R$ ' . number_format($valor_recebido, 2,',','.'))->style = 'text-align:left';
                $row->addCell('R$ ' . number_format($juros_recebido, 2,',','.'))->style = 'text-align:left';
            }
            $div->add( TElement::tag('div', TPanelGroup::pack('Valor Total Receber', $table2), ['class' => 'col-sm-6']));
        
        
            $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $vbox->add($div);
        }    
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }

        parent::add($vbox);
    
    }
}