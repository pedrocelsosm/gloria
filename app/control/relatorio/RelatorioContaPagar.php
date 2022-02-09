<?php

class RelatorioContaPagar extends TPage
{
    private $form; // form
    protected $data;
    
    function __construct()
    {
        parent::__construct();
        error_reporting(0);
        // creates the form
        $this->form = new BootstrapFormBuilder('form_RelatorioContaPagar_report');
        $this->form->setFormTitle( 'Relatório Contas Pagar' );
        
        $data_inicio = new TDate('data_inicio');
        $data_fim = new TDate('data_fim');
        
        $conta_id = new TDBCombo('conta_id', 'db_condominio', 'Conta', 'id', 'descricao');
        $status = new TCombo('status');
        $status->addItems(array('Liquidado' => 'Liquidado', 'Parcelado' => 'Parcelado', 'Pendente' => 'Pendente'));
              
        // create the form fields
        $this->form->addFields( [ new TLabel('Data Início', 'red') ], [ $data_inicio] ,
                                [ new TLabel('Data Fim', 'red') ], [ $data_fim ] );         
        $this->form->addFields( [ new TLabel('Conta') ], [ $conta_id ] ,
                                [ new TLabel('Status') ], [ $status]);
                                
        //set Mask
        $data_inicio->setMask('dd/mm/yyyy');
        $data_fim->setMask('dd/mm/yyyy');
        
        $output_type  = new TRadioGroup('output_type');
        $this->form->addFields( [new TLabel('Mostrar em:')],   [$output_type] );
        
        // define field properties
        $output_type->setUseButton();
        $options = ['html' =>'HTML', 'pdf' =>'PDF', 'rtf' =>'RTF', 'xls' =>'XLS'];
        $output_type->addItems($options);
        $output_type->setValue('pdf');
        $output_type->setLayout('horizontal');
        
        $this->form->addAction( 'Gerar Relatório', new TAction(array($this, 'onGenerate')), 'fa:download blue');
                
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        
        parent::add($vbox);      
}

 
    function onGenerate()
    {
        try
        {
            // get the form data into an active record Customer
            $this->data = $this->form->getData();
            $this->form->setData($this->data);
            
            $format = $this->data->output_type;
            
            // open a transaction with database ''
            $source = TTransaction::open('db_condominio');
                        
            // define the query
            $query = 'SELECT conta.descricao, conta_pagar.valor, conta_pagar.data_vencimento, conta_pagar.valor_pago, pessoa.nome, conta_pagar.status                            
                      FROM  conta_pagar, pessoa, conta
                      WHERE conta_pagar.pessoa_id = pessoa.id  AND
                            conta_pagar.conta_id = conta.id  AND
                            conta_pagar.data_vencimento BETWEEN :data_inicio AND :data_fim';
                                         
            if ( !empty($this->data->conta_id) )
            {
                $query .= " and conta_id = {$this->data->conta_id}";
            }

            if ( !empty($this->data->status) )
            {
                $query .= " and status = {$this->data->status}";
            }
            
            $filters = [];
            $filters['data_inicio'] = TDate::date2us($this->data->data_inicio);
            $filters['data_fim'] = TDate::date2us($this->data->data_fim);
                        
            $rows = TDatabase::getData($source, $query, null, $filters );
            
            if ($rows)
            {
                $widths = [140,120,140,370,130];
                
                switch ($format)
                {
                    case 'html':
                        $table = new TTableWriterHTML($widths);
                        break;
                    case 'pdf':
                        $table = new TTableWriterPDF($widths);
                        break;
                    case 'rtf':
                        $table = new TTableWriterRTF($widths);
                        break;
                    case 'xls':
                        $table = new TTableWriterXLS($widths);
                        break;
                }
                
                if (!empty($table))
                {
                    // create the document styles
                    $table->addStyle('header', 'Helvetica', '16', 'B', '#ffffff', '#4B8E57');
                    $table->addStyle('title',  'Helvetica', '10', 'B', '#ffffff', '#6CC361');
                    $table->addStyle('datap',  'Helvetica', '10', '',  '#000000', '#E3E3E3', 'LR');
                    $table->addStyle('datai',  'Helvetica', '10', '',  '#000000', '#ffffff', 'LR');
                    $table->addStyle('footer', 'Helvetica', '10', '',  '#2B2B2B', '#B5FFB4');
                    
                    $table->setHeaderCallback( function($table) {
                        $table->addRow();
                        $table->addCell('Relatório Conta Pagar', 'center', 'header', 5);
                        // Pega data inicio e data fim imprimindo no relatório 
                        $table->addRow();
                        $table->addCell('Data Início: ' . $this->data->data_inicio . ' - Data Fim: ' . $this->data->data_fim, 'center','title',5);
                       
                        $table->addRow();
                        $table->addCell('Conta', 'center', 'title');
                        $table->addCell('Vencimento', 'center', 'title');
                        $table->addCell('Valor', 'center', 'title');
                        $table->addCell('Pessoa', 'center', 'title');
                        $table->addCell('Valor Pago', 'center', 'title');
                        
                    });
                    
                    $table->setFooterCallback( function($table) {                        
                        $table->addRow();                                            
                        $table->addCell(date('d/m/Y h:i:s'), 'center', 'footer', 5);                        
                    });                    
                    
                    // controls the background filling
                    $colour= FALSE;                    
                    //Iniciada variável ValorTotal igual a zero
                    $Valor = 0;
                    $ValorPago = 0;
                    
                    // data rows
                    foreach ($rows as $row)
                    {                       
                        $style = $colour ? 'datap' : 'datai';
                        // Para converter data_vencimento no relatório
                        $row['data_vencimento'] = TDate::date2br($row['data_vencimento']);
                        
                        $table->addRow();
                        $table->addCell($row['descricao'], 'left', $style);
                        $table->addCell($row['data_vencimento'], 'center', $style);
                        $table->addCell($row['valor'], 'right', $style);
                        $table->addCell($row['nome'], 'left', $style);
                        $table->addCell($row['valor_pago'], 'right', $style);                        
                        
                        $Valor += $row['valor'];
                        $ValorPago += $row['valor_pago'];
                                                
                        $colour = !$colour;
                    }
                    
                    $table->addRow();
                    $table->addCell('Valor Total: ', 'left', 'footer', 1);
                    $table->addCell(number_format($Valor,2,',','.'), 'right', 'footer', 2);
                    $table->addCell(number_format($ValorPago,2,',','.'), 'right', 'footer', 3);
                    
                    $output = "app/output/tabular.{$format}";
                
                    // stores the file
                    if (!file_exists($output) OR is_writable($output))
                    {
                        $table->save($output);
                        parent::openFile($output);
                    }
                    else
                    {
                        throw new Exception(_t('Permission denied') . ': ' . $output);
                    }
                    
                    // shows the success message
                    new TMessage('info', 'Relatório gerado. Por favor, ative popups no navegador.');
                }
            }
            else
            {
                new TMessage('error', 'Registros não encontrado');
            }
    
            // close the transaction
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
