<?php

class ContasDashboard extends TPage
{
    /**
     * Class constructor
     * Creates the page
     */
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
            
            $valor          = ContaReceber::where('valor','>',0)->sumBy('valor');
            $valor_recebido = ContaReceber::where('valor_recebido','>',0)->sumBy('valor_recebido');
            $valor_pagar    = ContaPagar::where('valor','>',0)->sumBy('valor');
            $valor_pago     = ContaPagar::where('valor_pago','>',0)->sumBy('valor_pago');
            $juros_recebido = ContaReceber::where('juros_recebido','>',0)->sumBy('juros_recebido');
            $saldo          = ContaPagar::where('saldo','>',0)->sumBy('saldo');
          
            TTransaction::close();
            
            $indicator1 = new THtmlRenderer('app/resources/info-box.html');
            $indicator2 = new THtmlRenderer('app/resources/info-box.html');
            $indicator3 = new THtmlRenderer('app/resources/info-box.html');
            $indicator4 = new THtmlRenderer('app/resources/info-box.html');
            
            $indicator1->enableSection('main', ['title' => 'Valor a Receber', 'icon' => 'money-bill', 'background' => 'blue',
                                                'value' => 'R$ ' . number_format($valor,2,',','.') ] );
                                                
            $indicator2->enableSection('main', ['title' => 'Valor Recebido', 'icon' => 'money-bill', 'background' => 'green',
                                               'value'  => 'R$ ' . number_format($valor_recebido,2,',','.') ] );
            
            $indicator3->enableSection('main', ['title' => 'Valor a Pagar', 'icon' => 'money-bill', 'background' => 'orange',
                                                'value' => 'R$ ' . number_format($valor_pagar,2,',','.') ] );                                    
            
            $indicator4->enableSection('main', ['title' => 'Valor Pago', 'icon' => 'money-bill', 'background' => 'red',
                                               'value'  => 'R$ ' . number_format($valor_pago,2,',','.') ] );
            
            
            $div->add( TElement::tag('div', $indicator1, ['class' => 'col-sm-6']) );
            $div->add( TElement::tag('div', $indicator2, ['class' => 'col-sm-6']) );
            $div->add( TElement::tag('div', $indicator3, ['class' => 'col-sm-6']) );
            $div->add( TElement::tag('div', $indicator4, ['class' => 'col-sm-6']) );      
                       
            $table1 = TTable::create( [ 'class' => 'table table-striped table-hover', 'style' => 'border-collapse:collapse' ] );
            $table1->addSection('thead');
            $table1->addRowSet('Mês', 'Valor', 'Recebido', 'Juros');
            
            if ($valor_recebido)
            {
               
                $table1->addSection('tbody');
                
                $table1->addSection('tfoot')->style = 'color:blue';
                $row = $table1->addRow();
                $row->addCell( 'Total' );
                $row->addCell('R$&nbsp;' . number_format($valor, 2,',','.'))->style = 'text-align:left';
                $row->addCell('R$&nbsp;' . number_format($valor_recebido, 2,',','.'))->style = 'text-align:left';
                $row->addCell('R$&nbsp;' . number_format($juros_recebido, 2,',','.'))->style = 'text-align:left';
            }
            $div->add( TElement::tag('div', TPanelGroup::pack('A receber por mês', $table1), ['class' => 'col-sm-6']) );
            
            $table2 = TTable::create( [ 'class' => 'table table-striped table-hover', 'style' => 'border-collapse:collapse' ] );
            $table2->addSection('thead');
            $table2->addRowSet('Mês', 'Valor', 'Valor Pago', 'Saldo');

            if ($valor_pago)
            {
               
                $table2->addSection('tbody');
                
                $table2->addSection('tfoot')->style = 'color:red';
                $row = $table2  ->addRow();
                $row->addCell( 'Total' );
                $row->addCell('R$&nbsp;' . number_format($valor_pagar, 2,',','.'))->style = 'text-align:left';
                $row->addCell('R$&nbsp;' . number_format($valor_pago, 2,',','.'))->style = 'text-align:left';
                $row->addCell('R$&nbsp;' . number_format($saldo, 2,',','.'))->style = 'text-align:left';
            }            
                                   
            $div->add( TElement::tag('div', TPanelGroup::pack('Valor Pago no mês', $table2), ['class' => 'col-sm-6']) );
            
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
