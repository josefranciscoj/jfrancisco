<?php
######
global $database;

class solicita_acesso{
    private $cadastro ;
    public $contato;

    function solicita_acesso(){
        $this->cadastro = new stdClass();
        //$this->carrega_campos_teste();
        
    }
    
    function carrega_pagina(){
        
        if(isset($_POST["formulario"])){
           $this->retorna_form();
            
            
        }else{

            if(isset($_GET["ac"])){
                $ac = mosGetParam( $_REQUEST ,'ac' );
                switch ($ac){
                    case "reenvio":
                        if(isset($_GET["op"])){
                            $op = mosGetParam( $_REQUEST ,'op' ); // op=id usuario 
                            $this->carrega_reenvio_cadastro($op);
                        }else{
                            $this->carrega_form_geral();
                        }
                        
                        return;
                    case "ativa":
                        if(isset($_GET["op"])){
                            $op = mosGetParam( $_REQUEST ,'op' ); // op=id usuario 
                            $this->ativa_cadastro($op);
                            ;
                        }else{
                            $this->carrega_form_geral();
                        }
                        return;
                    default:
                        $this->carrega_form_geral();
                        return;
                }
            }else{
                $this->carrega_form_geral();
            }
        }


        /*
            $this->retorna_form();
        }else{
        }
         * 
         */
    }
    

    function carrega_campos_teste(){
        $this->segmento = 'Acadêmico';
        $this->estado = 'SP';
        $this->contato[nome] = 'Lucio Banevicius';
        $this->contato[email] = 'lbanevi@hexis.com.br';
        $this->contato[cargo] = 'Analista';
        $this->contato[ddd] = '11';
        $this->contato[telefone] = '98181-1180';
        $this->contato[telefone_complemento] = 'Direto';
        $this->contato[empresa] = 'Hexis ';
        $this->contato[cnpj] = '08.097.105/0001-12';
        $this->senha = '123456';
        $this->senha_confirma = '123456';
    }
    
    function salvar_cadastro(){
        
    }
    
    function ativa_cadastro($_md5_idusuario){
        global $database;
        
        $database->setQuery("
            select id 
            from mos_users 
            where md5(id)='$_md5_idusuario'
        ");
        $le = $database->loadObjectList();
        if(count($le)){
            $_idUsuario = $le[0]->id;
        }
        
        $database->setQuery("update mos_users set validado=true where id=$_idUsuario;");
        $database->query();  
        
        $this->carrega_aviso_cliente_validado(true);
        
    }
    
    
    function carrega_aviso_cliente_validado($validado = false){
        
        if($validado){
            ?>
            <div id="d_retorno_cadastro" class="cadastro">
                <h2>Obrigado, seu email foi validado com Sucesso.</h2>
                <p>
                    <br/>Faça seu login e acesse agora o maior portfólio do mercado analítico.
                    <!--br/><strong><a href="http://www.myhexis.com.br">Clique aqui</a>.</strong-->
                    <br/><strong><a href="localhost/myhexis">Clique aqui</a>.</strong>
                </p>

            </div>
            <?php
        }else{
            ?>
            <div id="d_retorno_cadastro" class="cadastro">
                <h2>Ocorreu um erro ao tentar validar seu cadastro.</h2>            
                <p>
<!--                    <strong>Por favor, tente se cadastrar novamente</strong>-->
                    <br/>Por favor, tente se cadastrar novamente ou entre em contato com nosso suporte pelo email: 
                    <strong><a href="mailto:myhexis@hexis.com.br">myhexis@hexis.com.br</a></strong>
                </p>

                <div class="aviso_nao_recebi">
                    Caso não receba o email, <a href="index.php?recupera=solicitaacesso&ac=reenvio&op=<?php echo $_md5_id; ?>">clique aqui</a> para solicitar o reenvio.
                </div>
            </div>
            <?php
        }
    }
    
    
    
    
    function retorna_form(){
        global $database;

        $this->cadastro->segmento       = mosGetParam( $_REQUEST ,'segmento' );
        $this->cadastro->estado         = mosGetParam( $_REQUEST ,'estado' );
        $this->cadastro->senha          = mosGetParam( $_REQUEST ,'senha' );
        $this->cadastro->senha_confirma = mosGetParam( $_REQUEST ,'senha_confirma' );
        $this->cadastro->contato        = $_POST[contato];
        
        require_once '/classes/cliente.php'; 
        $cliente = new cliente();
        $COD_CLI = $cliente->verifica_existe_cliente_CNPJ($this->cadastro->contato[cnpj]);
        
        if(!$COD_CLI > 0){
            // não existe cliente
            $cliente->DES_RAZAO      = $this->cadastro->contato[empresa];
            $cliente->DES_ABREV      = '';
            $cliente->DES_CNPJ       = $this->cadastro->contato[cnpj];
            $cliente->DES_IE         = '';
            $cliente->DES_IM         = '';
            $cliente->REPRESENTANTE  = '';
            $cliente->ATIVO          = 1;
            $cliente->logo           = '';
            $cliente->GRUPO          = '';
            $cliente->VENDA_REVENDA  = 'V';
            $cliente->ALIQUOTA_ICM   = 18;
            $cliente->COD_TABELA     = 'TCHEIA18';
            $cliente->ESTADO         = 'SP';
            $cliente->CEP            = '';
            
            $COD_CLI = $cliente->cadastraCliente();
        }
        
        // aqui deve ter o COD_CLI
        // cadastrar Usuario
        //
        require_once '/classes/usuario_cadastro.php'; 
        $us_cad = new usuario_cadastro();
        
        $id_usuario = $us_cad->verificaSeUsuarioJaCadastrado_email_COD_CLI($this->cadastro->contato[email], $cliente->COD_CLI);
        
        
        if(!$id_usuario > 0){
        
            $us_cad->COD_CLI        = $cliente->COD_CLI;
            $us_cad->block          = 0;  
            $us_cad->gid            = 0;  
            $us_cad->name           = $this->cadastro->contato[nome];  
            $us_cad->username       = $this->cadastro->contato[email];  
            $us_cad->email          = $this->cadastro->contato[email];
            $us_cad->password       = $this->cadastro->senha;
            $us_cad->cargo          = $this->cadastro->contato[cargo];
            $us_cad->tel            = $this->cadastro->contato[ddd] ." ". $this->cadastro->contato[telefone];
            $us_cad->COD_CENTRO     = '001';
            $us_cad->COD_CONTATO    = 0;
            $us_cad->last_visit     = '1900-01-01';
            $us_cad->FLAG_CADASTRO  = 0;
            
            $id_usuario = $us_cad->cadastrarUsuario(); 
            $us_cad->enviaEmailUsuarioCadastrado(); // envia email
        }
//                echo "id_usuario: <br/>" . $us_cad->id . "<br/>";
//                echo "COD_CLI: <br/>" . $COD_CLI . "<br/>";
                    
        
        $this->carrega_aviso_envio_cadastro("",$us_cad->get_md5id($id_usuario));
    }
    
    function carrega_aviso_envio_cadastro($_opcao="", $_md5_id){
        
        if($_opcao =="reenvio"){
            ?>
            <div id="d_retorno_cadastro" class="cadastro">
                <h2>Obrigado por seu cadastro.</h2>            
                <p>
                    <strong>Reenviamos uma solicitação de ativação para seu email.</strong>
                    <br/>Por favor, siga as instruções contidas no corpo do email para validação do seu cadastro.
                </p>

                <div class="aviso_nao_recebi">
                    Caso não receba o email, <a href="index.php?recupera=solicitaacesso&ac=reenvio&op=<?php echo $_md5_id; ?>">clique aqui</a> para solicitar o reenvio.
                </div>
            </div>
            <?php
        }else{
            ?>
            <div id="d_retorno_cadastro" class="cadastro">
                <h2>Obrigado por seu cadastro.</h2>            
                <p>
                    <strong>Enviamos uma solicitação de ativação para seu email.</strong>
                    <br/>Por favor, siga as instruções contidas no corpo do email para validação do seu cadastro.
                </p>

                <div class="aviso_nao_recebi">
                    Caso não receba o email, <a href="index.php?recupera=solicitaacesso&ac=reenvio&op=<?php echo $_md5_id; ?>">clique aqui</a> para solicitar o reenvio.
                </div>
            </div>
            <?php
        }
    }
    
    function carrega_reenvio_cadastro($_idUsuario=""){
        global $database;
        if ($_idUsuario != ""){
            
            $database->setQuery("select * from mos_users where md5(id)='". $_idUsuario ."'");
            
            $le = $database->loadObjectList();
            if(count($le)){
                require_once '/classes/usuario_cadastro.php'; 
                $us_cad = new usuario_cadastro();
                $us_cad->id     = $le[0]->id;
                $us_cad->name   = $le[0]->name;
                $us_cad->email  = $le[0]->email;
                $us_cad->enviaEmailUsuarioCadastrado(); 
                
                $this->carrega_aviso_envio_cadastro("reenvio", $us_cad->get_md5id($us_cad->id));
                
            }else{
                echo "usuário não encontrado";
            }
            
        }
        
    }
    
    
    
    
    
    function carrega_form_geral(){
	
        $estados = $this->getEstados();
        ?>
		<!--INICIO - div para montal modal de alerta de email já cadastrado-->		
		<div id="modal_solicita_acesso" title="ATENÇÃO" style="display:none">
		  <p>Email já cadastrado, deseja resgatar dados de acesso?</p>
		</div> 
        <!--FIM - div para montal modal de alerta de email já cadastrado-->
		
		<form id="formCadastro" class="cadastro" onsubmit="return validaFormContato(this);" method="post"  action="index.php?recupera=solicitaacesso" >	
            <h2>Cadastro de Cliente ao site MyHexis</h2>
            
            <input type="hidden" id="formulario" name="formulario"  value="contato"/> 
            <fieldset>
                <legend>Torne suas compras mais ágeis, a qualquer hora, com informações detalhadas dos produtos, 
                    preços, disponibilidade e muitos outros benefícios. Preencha o formulário abaixo    : </legend>

                <p>
                </p>
                
                
                <div class="campo_form">
                    <label class="lbl_txt" for="segmento">Segmento de Atuação</label>
                    <select name="segmento" id="segmento">
                        <?php
                        if(isset($this->segmento)){
                            echo "<option value='$this->segmento' selected >$this->segmento</option>";
                        }else{
                            echo "<option value='0' selected>Selecione..</option>";
                        }
                        ?>
                        <option value="Acadêmico">Acadêmico</option>
                        <option value="Açúcar e Álcool">Açúcar e Álcool</option>
                        <option value="Alimentos">Alimentos</option>
                        <option value="Bebidas">Bebidas</option>
                        <option value="Cosméticos">Cosméticos</option>
                        <option value="Engenharia">Engenharia</option>
                        <option value="Farmacêutico">Farmacêutico</option>
                        <option value="Hidrelética">Hidrelétrica</option>
                        <option value="Licitação">Licitação</option>
                        <option value="Papel e Celulose">Papel e Celulose</option>
                        <option value="Petroquímico">Petroquímico</option>
                        <option value="Químico">Químico</option>
                        <option value="Revendas">Revendas</option>
                        <option value="Saneamento">Saneamento</option>
                        <option value="Outros">Outros</option>
                    </select>
                </div>

                <div class="campo_form">
                    <label  class="lbl_txt">Selecione o Estado</label>
                    <select id="estado" name="estado" >
                        <option value="0" <?php echo !isset($this->estado)?"Selected":false;  ?>  >Selecione..</option>

                        <?php 
                        foreach ($estados as $_estados){
                            echo "<option value='$_estados->DES_SIGLA' ". ($this->estado==$_estados->DES_SIGLA?"selected":false) ." >$_estados->DES_ESTADO</option>";
                        }
                        ?>
                    </select> 
                </div> 
               
                
                <div class="campo_form">
                    <label class="lbl_txt" for="contato_nome">Nome</label>
                    <input class="txt_grande" name="contato[nome]" id="contato_nome" value="<?php echo $this->contato[nome]; ?>" type="text" />
                    <span id="msg_nome" class="msg_invalid">Preencher Nome</span>
                </div>
                <div class="campo_form">
                    <label class="lbl_txt">Email</label>
                    <input class="txt_grande" name="contato[email]" onblur="return checaEmailContato();" id="contato_email" value="<?php echo $this->contato[email]; ?>" type="text" /><span id="infoContatoEmail"></span>
					<span id="msg_email" class="msg_invalid">Preencher Email</span>
                </div>
                <div class="campo_form">
                    <label class="lbl_txt">Cargo</label>
                    <input class="txt_grande" name="contato[cargo]" id="contato_cargo" value="<?php echo $this->contato[cargo]; ?>" type="text" />
                    <span id="msg_cargo" class="msg_invalid">Preencher Cargo</span>
                </div>

                <div class="campo_form">
                    <label class="lbl_txt">Telefone</label>
                    <input class="txt_ddd numero" maxlength="2" name="contato[ddd]" id="contato_ddd" value="<?php echo $this->contato[ddd]?>" type="text"/>
                    <input  class="txt_telefone numero" maxlength="10" name="contato[telefone]" id="contato_telefone" value="<?php echo $this->contato[telefone]; ?>" type="text"/>
                    <label for="txt_telefone_complemento" class="lbl_txt2">Complemento</label>
                    <input  class="txt_telefone" name="contato[telefone_complemento]" id="contato_telefone_complemento" value="<?php echo $this->contato[telefone_complemento]; ?>" type="text"/>
                    <span id="msg_telefone" class="msg_invalid">Preencher Telefone</span>
                </div>

                

                <div id="campo_nome_universidade" class="campo_form">
                    <label id="lbl_empresa" class="lbl_txt">Empresa</label>
                    <input class="txt_grande" name="contato[empresa]" id="contato_empresa" value="<?php echo $this->contato[empresa]; ?>" type="text" />
                    <span id="msg_email" class="msg_invalid"></span>
                </div>
                
                <div class="campo_form">
                    <label id="lbl_cnpj" class="lbl_txt">CNPJ</label>
                    <input class="txt_cnpj numero" maxlength="18" name="contato[cnpj]" id="contato_cnpj" value="<?php echo $this->contato[cnpj]; ?>" type="text"/>
                    <span class="txt_comentario">(somente números)</span>
                    <span id="msg_cnpj" class="msg_invalid">Preencher CNPJ</span>
                </div>
                
                <legend>Dados de Acesso</legend>
                <div class="campo_form">
                    <label class="lbl_txt">Crie uma senha</label>
                    <input name="senha" maxlength="15" id="contato_senha" value="<?php echo $this->senha?>" type="password"/>
                    <span class="txt_comentario">(mínimo 5 letras ou números)</span>
                </div>
                <div class="campo_form">
                    <label class="lbl_txt">Digite a senha novamente</label>
                    <input name="senha_confirma" maxlength="15" id="contato_senha_confirma" value="<?php echo $this->senha_confirma?>" type="password"/>
                </div>
                
                <div id="msg_form">
                    
                </div>
                
                <div class="campo_form">
                    <label class="lbl_txt">&nbsp;</label>
                    <input name="enviar" id="bt_enviar" value="Enviar" type="submit"  />
                </div>
                
            </fieldset>

        </form>
        <?php
    }
    
    function getEstados(){
        global $database;

        $database->setquery("select COD_ESTADO, Lower(DES_ESTADO) DES_ESTADO, DES_SIGLA from ESTADO order by DES_ESTADO");
        $estados = $database->loadObjectList();
        
        return $estados;
        
    }
    
    

}


