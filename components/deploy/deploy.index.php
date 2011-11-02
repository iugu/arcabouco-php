<div class="buttons controls">

<div class="box"><img value_from="%$image_deploy%" alt="" />Sistema de Instalação Automática</div>

<div class="cbf mb20">

<a href="/deploy/update"><img value_from="%$image_wand%" alt="" />Instalar / Atualizar</a>

</div>

<div class="box"><img value_from="%$image_deploy%" alt="" />Configurações do Servidor</div>

<form name="edit_record" value_from="%$save_action%" method="post">

	<div class="p10 cbf">

	<div class="info">Preenchimento obrigatório</div>

	<label for="host">Servidor<img value_from="%$image_asterisk%" alt=""  />
	<span>
		Endereço do servidor de hospedagem
	</span>
	</label>
	<input id="host" name="host" class="required" value_from="%$deploy_configuration.host%" style="width:400px" />

	<label for="username">Usuário<img value_from="%$image_asterisk%" alt=""  />
	<span>
		Usuário para Login via SSH (Consultar Ajuda / Hospedagem)
	</span>
	</label>
	<input id="username" name="username" class="required" value_from="%$deploy_configuration.username%" style="width:300px" />
	
	<label for="directory">Diretório<img value_from="%$image_asterisk%" alt=""  />
	<span>
		Diretório onde é feito a instalação
	</span>
	</label>
	<input id="directory" name="directory" class="required" value_from="%$deploy_configuration.directory%" style="width:400px" />
	
	<label for="password">Senha<img value_from="%$image_asterisk%" alt=""  />
	<span>
		Senha do usuário
	</span>
	</label>
	<input id="password" name="password" class="required" value_from="%$deploy_configuration.password%" style="width:200px" />

	<input type="hidden" name="_method" value="POST" />

	<a href="#" onclick='javascript:document.edit_record.submit();return false' class="positive">
	<img value_from="%$image_tick%" alt="" /> 
		Salvar
	</a>
	
	<!-- <a href="/deploy/install"><img value_from="%$image_tool%" alt="" />Instalar</a> -->

	</div>

	</form>

</div>
