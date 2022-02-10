<?php

use Adianti\Widget\Wrapper\TDBCombo;

class RelatorioEstadoCidade extends TPage
{
    private $form; // form
    protected $data;
    
    function __construct()
    {
        parent::__construct();
        //error_reporting(0);
        // creates the form
        $this->form = new BootstrapFormBuilder('form_RelatorioEstadoCidade_report');
        $this->form->setFormTitle( 'Relatório Estado-Cidade' );      
        
        $estado_id = new TDBCombo('estado_id', 'db_condominio', 'Estado', 'id', 'nome');
                
        $this->form->addFields( [ new TLabel('Estado') ], [ $estado_id ]);
        
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
            
            // open a transaction with database
            $source = TTransaction::open('db_condominio');
                        
            // define the query
            $query = 'SELECT cidade.nome AS nome_cidade, estado.nome, estado.uf                        
                      FROM   cidade, estado
                      WHERE  estado.id = cidade.estado_id ';
                                         
            if ( !empty($this->data->estado_id) )
            {
                $query .= " and estado_id = {$this->data->estado_id}";
            }
            
            $filters = [];
                        
            $rows = TDatabase::getData($source, $query, null, $filters );
            
            if ($rows)
            {
                $widths = [300,250,100];
                
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
                        $table->addCell('Relatório Cidade Estado', 'center', 'header', 3);
                        // Pega data inicio e data fim imprimindo no relatório 
                        $table->addRow();
                        //$table->addCell('Data Início: ' . $this->data->data_inicio . ' - Data Fim: ' . $this->data->data_fim, 'center','title',5);
                       
                        $table->addRow();
                        $table->addCell('Cidade', 'center', 'title');
                        $table->addCell('Estado', 'center', 'title');
                        $table->addCell('UF', 'center', 'title');                        
                    });
                    
                    $table->setFooterCallback( function($table) {                        
                        $table->addRow();                                            
                        $table->addCell(date('d/m/Y h:i:s'), 'center', 'footer', 3);                        
                    });                    
                    
                    // controls the background filling
                    $colour= FALSE;

                    // data rows
                    foreach ($rows as $row)
                    {                       
                        $style = $colour ? 'datap' : 'datai';                      
                        
                        $table->addRow();
                        $table->addCell($row['nome_cidade'], 'left', $style);
                        $table->addCell($row['nome'], 'center', $style);
                        $table->addCell($row['uf'], 'center', $style);
                        
                        
                                                
                        $colour = !$colour;
                    }
                    
                    $table->addRow();
                    $table->addCell('Valor Total: ', 'left', 'footer', 1);
                    $table->addCell($soma_cidade, 'right', 'footer', 2);

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
