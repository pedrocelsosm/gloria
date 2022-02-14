<?php

use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;

class RelatorioPessoa extends TPage
{
    private $form; // form
    protected $data;
    
    function __construct()
    {
        parent::__construct();

        // creates the form
        $this->form = new BootstrapFormBuilder('form_RelatorioPessoa_report');
        $this->form->setFormTitle( 'Relatório Pessoa' );      
        
        $pessoa_id = new TDBUniqueSearch('pessoa_id', 'db_condominio', 'Pessoa', 'id', 'nome');
              
        $this->form->addFields( [ new TLabel('Pessoa') ], [ $pessoa_id ]);        
        
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
            $query = 'SELECT pessoa.nome, pessoa.email, pessoa.fone, unidade.descricao                        
                      FROM   pessoa, unidade
                      WHERE  unidade.pessoa_id = pessoa.id';
                      
            $criteria = new TCriteria;
            $criteria->add(new TFilter('nome', 'like', '%nome%'), TExpression::OR_OPERATOR);    
                                         
            if ( !empty($this->data->pessoa_id) )
            {
                $query .= " and pessoa_id = {$this->data->pessoa_id}";
            }
            
            $filters = [];
                        
            $rows = TDatabase::getData($source, $query, null, $filters );
            
            if ($rows)
            {
                $widths = [300,250,150, 150];
                
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
                        $table->addCell('Relatório Pessoa', 'center', 'header', 4);
                        
                        $table->addRow();
                        $table->addCell('Nome', 'center', 'title');
                        $table->addCell('Email', 'center', 'title');
                        $table->addCell('Fone', 'center', 'title');
                        $table->addCell('Imóvel', 'center', 'title');                        
                    });
                    
                    $table->setFooterCallback( function($table) {                        
                        $table->addRow();                                            
                        $table->addCell(date('d/m/Y h:i:s'), 'center', 'footer', 4);                        
                    });                    
                    
                    // controls the background filling
                    $colour= FALSE;
                    $contador = 0;
                    // data rows
                    foreach ($rows as $row)
                    {                       
                        $style = $colour ? 'datap' : 'datai';                      
                        
                        $table->addRow();
                        $table->addCell($row['nome'], 'left', $style);
                        $table->addCell($row['email'], 'left', $style);
                        $table->addCell($row['fone'], 'right', $style);
                        $table->addCell($row['descricao'], 'right', $style);
                        
                        $contador++;
                                                
                        $colour = !$colour;
                    }
                    
                    $table->addRow();
                    $table->addCell('Total de pessoas: ', 'left', 'footer', 1);
                    $table->addCell($contador, 'right', 'footer', 3);

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
