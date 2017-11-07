<?php
//MODIFICADO: LEONARDO 23/10/2017 03:00 pm,se agrega al menu de tareas la notificacion de circular
//MODIFICADO: LEONARDO 10/10/2017 09:10 AM,MODIFICACION DE ENLACE DE CIRCULARES
//MODIFICADO: LEONARDO 20/09/2017 10:53 AM,MODIFICACION DE ENLACE DE NUEVAS TARES
//MODIFICADO: NELSON 27/09/2017 14:10 PMM,OCULTAR aMIGO SECRETO
//MODIFICADO: leonardo 28/09/2017 14:19 PM,CIRCULARE
session_start(); 
//require('../bodega/conexion/conexion.php');
include("../virtualmax/conexion/conexion.php");
include('../virtualmax/ventaonline/dao/daocontratos.php');
include("dao/logindao.php");
$instancia_contrato = new daocontratos();
include("control_notificaciones.php");
include("../virtualmax/notificaciones/dao/dao_notificacion.php");
validaCircularFuncionario();
//require('../virtualmax/conexion/conexion.php');

$_SESSION['departamento'];
if(($_SESSION['Rol']=="")  or ($_SESSION['Rol']==18) or ($_SESSION['NombreUsuario']=='')){
 header('location:../accessdenied.php');
 }
//$ipPublica = '190.253.66.34';
$ipPublica='192.168.1.11';
//$ipPublica = '181.225.100.118';
$fecha = date('Y-m-d');
echo $idLogIn = $_SESSION['Id'];
$idRolTarea = $_SESSION['idRolTarea'];
$fechaClave = $_SESSION['fechaClave'];
$rol = $_SESSION['Rol'];
$documento = $_SESSION['documento'];
if ($fecha >= $fechaClave){
	echo 
	"<script>
		alert('Debe cambiar su contraseña para poder continuar');
		window.location.href='vista/usuario/clave.php';
	</script>";
}elseif(!isset($_SESSION['Id'])){
	echo 
	"<script> 	window.location.href='index.php'; 	</script>";
}

//include ('Control/conexion.php');

include ('Dao/tareas/tareasDao.php');
include ("Dao/tareas/redireccionDao.php");
include ("Dao/novedadesFuncionario/novedadesDao.php");
include("dao/ingresoProveedores/ingresoProveedoresDao.php");
include('../virtualmax/mesaAyuda/dao/mesaAyudaDao.php');

//consulta nuevas visitas con licitaciones encontradas por aprobar
$conexion=new MySQL("oclaeconomia");
$consutla=$conexion->sentenciasql("
SELECT 
	COUNT(*)
 FROM 
 	menu_novedades mn,
	licitaciones_links_registro_visitas ll 
 WHERE
 ll.`id`=mn.`idLicitacionesRegistroVisitas` AND
   iddestinatario='".$documento."' and 
   estado=0 and
    tipo_novedad=1 and 
	ll.`acierto`=1
	");
$resn = mysql_fetch_array($consutla);

//consulta nuevos check list por aprobar
$consutla=$conexion->sentenciasql("
			select count(*) from menu_novedades where iddestinatario='".$documento."' and estado=0 and tipo_novedad=2");
$res_checklist = mysql_fetch_array($consutla);

$consultacalendario=$conexion->sentenciasql("

SELECT COUNT(*) FROM  tcalendario WHERE fecha='".$fecha."' and documento='".$_SESSION['documento']."'")or die(mysql_error());
$calendatio=mysql_fetch_array($consultacalendario);

$tareasDao = new tareasDao();
$totalTareas = $tareasDao->totalTareas();
$tareasCierres = $tareasDao->tareasCierre($idLogIn);
$ingresosDao = new ingresoProveedoresDao();

$redireccionDao = new redireccionDao();
$totalRedireccionadas = $redireccionDao->totalRedireccionadas($idLogIn);

$novedadesDao = new novedadesFuncionarioDao();
if($idRolTarea > 20)
{
	$novedades = $novedadesDao->novedadesFuncionario($idLogIn);
}else if($idRolTarea > 3 && $idRolTarea != 14)
{
	$novedades = $novedadesDao->novedadesDirector($idLogIn);
}else
{
	$novedades = $novedadesDao->novedades();
}

$novedadSinFinalizar = '';
while ($novedad = mysql_fetch_array($novedades))
{
	$totalNovedades = $novedad['Pendiente'] + $novedad['Activa'];
	if($totalNovedades > 0)
	{
		$novedadSinFinalizar = $totalNovedades;
	}
}

$tPendiente = 0;
$tActiva = 0;
$tFinalizada = 0;
$tReservada = 0;
$tRedireccionadas = 0;

while ($estado = mysql_fetch_array($totalTareas))
	{
		if ($idLogIn == $estado[5])
			{
				if ($estado[11] == "Pendiente" && $estado[12] != "Si")
			  		{
				 		$tPendiente++;
			 		}
			  	if ($estado[11] == "Activa" && $estado[12] != "Si")
			  		{
						$tActiva++;
			  		}
			  	if ($estado[11] == "Finalizada" && $estado[12] != "Si")
			  		{
						$tFinalizada++;
			  		}
				if ($estado[12] == "Si")
			  		{
						$tReservada++;
			  		}
		  	}
	 }
if (mysql_num_rows($totalTareas) > 0)
	{
	 	mysql_data_seek($totalTareas, 0);
	}
	
$tareasRed = mysql_fetch_array($totalRedireccionadas);
$tRedireccionadas = $tareasRed['Pendiente'] + $tareasRed['Activa'];
$tareasCierre = mysql_num_rows($tareasCierres);

$ingresosContraentrega = $ingresosDao->contraentregaPendiente();
$pendienteContraE = mysql_fetch_array($ingresosContraentrega);

//************************MESA DE AYUDA****************************\\
/*if($idRolTarea == 5 || $idRolTarea == 51)
{
	$mesaAyudaDao = new mesaAyudaDao();
	$pendientes = $mesaAyudaDao->solicitudesASistemas('Pendiente');
	$totalSolPendientes = mysql_num_rows($pendientes);
}
else
{
	$totalSolPendientes = '';
}
*/

//*****************************************************************\\
//Consulta si hoy es sabado para enviar los contratos proximos a vencer
//*****************************************************************\\

/*$hoy  = getdate();
//echo 'Hoy es..:'. $hoy['weekday'];
if($hoy['weekday'] == 'Saturday' or $hoy['weekday'] == 'Wednesday'){//solo el sabado y miercoles realiza este proceso
	//1. consulta si ya se realizo el proceso

*/	$result_ec = $instancia_contrato->consultaEnvioCorreos();
	$obj = mysql_fetch_array($result_ec);
	
	if($obj['conteo']==0){ //Si no hay historial para el dia de hoy, ingresa el registro
		header('location:../virtualmax/licitaciones/contratos/control.php');
	//}//else{echo 'Ya hay ingreso';}
	}
?>



<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta content="900" http-equiv="REFRESH"> </meta>

<title>Mediqboy: Inicio</title>

<link rel="stylesheet" href="../bodega/jq/themes/base/style.css" />
<link rel="stylesheet" type="text/css" href="css/estilosMenu.css?v=6"/>
<!--<script src="jq/login.js" type="text/javascript"></script>-->


<link rel="stylesheet" type="text/css" href="../virtualmax/jq/lib/jquery-ui.css"/><!-- Dialog -->
<script type="text/javascript" src="../virtualmax/jq/lib/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="engine1/jquery.js"></script>
<script type="text/javascript" src="../virtualmax/jq/lib/jquery-ui.js"></script>
<script src="../virtualmax/jq/push-notificacion/push.min.js"></script>
<script src="js/notificaciones.js?ver=4"></script>

<script>
$(function () {
	//INICIO dialog de novedades cuando hay una licitacion nueva de una visita
	var dialogo_nuevo =  $("#menu_novedades1").dialog({
	  autoOpen:true, width:820, height:700, modal:true, 
	  buttons:{"Cerrar":cerrar}	
	});
  
  // JavaScript Document

 
 function repetir(){
  setTimeout(function() {
      $("#recor").fadeOut(800);
      },800);
    setTimeout(function() {
    $("#recor").fadeIn(800);
      },800);
	 }
 	setInterval(repetir,800);
  
 	function cerrar(){
		$("#menu_novedades1").dialog("close"); 
	 	}
	
	$("#select").change(function(event){
    	var id = $("#select").find(':selected').val();
		if(id!=''){
			$("#carga").load('cargadatos.php?id='+id);
			console.log(id);
					}
		else if(id==''){
			$("#encontrada").text('');
			$("#localizada").html('');
			$("#tipo").text('');
			$("#valor").text('');
			$("#fechacierre").text('');
			$("#comentario").text('');
		}
	});
	//FIN dialog de novedades cuando hay una licitacion nueva de una visita
	
	//INICIO dialog de novedades cuando hay una licitacion DE UNA CHECK LIST
/*	var dialogo_nuevo =  $("#menu_novedades2").dialog({
	  autoOpen:true, width:820, height:700, modal:true, 
	  buttons:{"Cerrar":cerrar1}	
	});
  
 	function cerrar1(){
		$("#menu_novedades2").dialog("close"); 
	 	}
	
	$("#select").change(function(event){
    	var id = $("#select").find(':selected').val();
		if(id!=''){
			$("#carga").load('cargadatos.php?id='+id);
			
		}
		else if(id==''){
			$("#encontrada").text('');
			$("#localizada").html('');
			$("#tipo").text('');
			$("#valor").text('');
			$("#fechacierre").text('');
			$("#comentario").text('');
		}
	});*/
	//FIN dialog de novedades cuando hay una licitacion nueva DE UNA CHECK LIST


});   
</script>



<script src="SpryAssets/SpryTabbedPanels.js" type="text/javascript"></script>
<script src="SpryAssets/SpryMenuBar.js" type="text/javascript"></script>
<script src="SpryAssets/SpryTabbedPanels.js" type="text/javascript"></script>
<link href="SpryAssets/SpryMenuBarHorizontal.css" rel="stylesheet" type="text/css" />
<link href="SpryAssets/SpryTabbedPanels.css" rel="stylesheet" type="text/css" />
<link rel="shortcut icon" href="../../virtualmax/imagenes/iconos/favicon.ico" type="image/png"/>

<style type="text/css">
body {
	background-size:contain;
	background-image:url(imagenes/fondo.jpg);
	background-repeat:no-repeat;

	background-size:100%;
    
}
textarea
{
	resize:none;
	font-size:36px;
	font-weight:bold;
}
a:link {
	text-decoration: none;
}
a:visited {
	text-decoration: none;
}
a:hover {
	text-decoration: none;
}
a:active {
	text-decoration: none;
}
.cod_barras {
	font-family: c39HrP24DlTt;
}
.ean13 {
	font-family: EAN-13;
}
.fre1 {
	font-family: Free 3 of 9, Free 3 of 9 Extended;
}
.free2 {
	font-family: Free 3 of 9, Free 3 of 9 Extended;
}
.codex {
	font-family: CODE3X;
}
#TabbedPanels1 .TabbedPanelsContentGroup .TabbedPanelsContent.TabbedPanelsContentVisible table tr td {
	font-weight: bold;
}
</style>
 <link href="../SpryAssets/SpryTabbedPanels.css" rel="stylesheet" type="text/css" />
</head>

<body onload="mueveReloj()">
<div id="notificar"></div>
<div id="notificarMesa"></div>


<?php 
$resn[0];
	if($resn[0]>0){
?>
<div id="carga"></div>
<div id="menu_novedades1" hidden="" title="Se han contrado nuevas Licitaciones..." align="center" style="background-color:#C6FFC6; font-size:17px" >
<form action="menu.php" method="post">
<table width="700" border="1" align="center" cellpadding="0" cellspacing="0" bgcolor="">
  <tr>
    <td width="348">Seleccione Visita: </td>
    <td width="477"><label for="select"></label>
    <?php 
if(isset($_POST['obs']))
	{
		$conexion=new MySQL("oclaeconomia");	
		$observacion=$_POST['obs'];
		$idlicitacionesregistrovisitas=$_POST['select'];		
		$sql="update menu_novedades set 
			estado=1,comentarios='".$observacion."',fechahoraenterado=now()
		where 
			idlicitacionesregistrovisitas=".$idlicitacionesregistrovisitas."
			and iddestinatario=".$_SESSION['documento'];
		$consutla3=$conexion->sentenciasql($sql) or die(mysql_error());
		//header("location:menu.php");
}

	  
	  /*
	  	  SELECT llv.`entidad`,mn.`idLicitacionesRegistroVisitas`
FROM licitaciones_links_visitas llv,
     licitaciones_links_registro_visitas llrv,
     menu_novedades mn
WHERE 
      llv.`id`=llrv.idlinksvisitas 
	  AND llrv.id=mn.`idLicitacionesRegistroVisitas` 
	  AND mn.`estado`=0 
	  AND mn.tipo_novedad=1
	  AND llrv.`acierto`=0
  GROUP BY llv.`entidad`,mn.`idLicitacionesRegistroVisitas`*/
	  
	  
	  $conexion=new MySQL("oclaeconomia");
	  
	$consutla2 = $conexion->sentenciasql("  
	SELECT
		 llv.entidad,
		 mn.idLicitacionesRegistroVisitas,
		 llrv.acierto
	FROM 
		licitaciones_links_visitas llv,	licitaciones_links_registro_visitas llrv,
		menu_novedades mn
	wHERE 
		llv.id=llrv.idlinksvisitas 
		AND llrv.id=mn.idLicitacionesRegistroVisitas 
		AND acierto=1 and mn.estado=0 and  mn.`idDestinatario`='".$_SESSION['documento']."'
		GROUP BY 
		llv.entidad, mn.idLicitacionesRegistroVisitas")or die(mysql_error());
	  
	 // echo "num:".mysql_num_rows($consutla2)."-";
	  ?>
      <select name="select" id="select" required>
        <option value="">Seleccione Entidad con licitacion... </option>
        <?php 
		while($resli=mysql_fetch_array($consutla2)){
			$entidad=substr($resli['entidad'],0,40);
		?>
        	<option value="<?php echo $resli['idLicitacionesRegistroVisitas']?>"><?php echo $entidad.'('.$resli['idLicitacionesRegistroVisitas'].')'?></option>
        <?php }?>        
      </select></td>
  </tr>
  <tr>
    <td>Encontrada por:</td>
    <td id="encontrada">&nbsp;</td>
  </tr>
  <tr>
    <td>Localizada desde:</td>
    <td id="localizada">&nbsp;</td>
  </tr>
  <tr>
    <td>Tipo:</td>
    <td id="tipo">&nbsp;</td>
  </tr>
  <tr>
    <td>Valor:</td>
    <td id="valor">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" align="center">Cronograma licitacion</td>
  </tr>
  <tr>
    <td colspan="2" align="center"><table width="727" border="1">
      <tr>
        <td height="24" colspan="5" align="center">Pliegos</td>
        <td height="24" align="center" id="pliegos">&nbsp;</td>
        </tr>
      <tr>
        <td height="24" colspan="6" align="center" style="font-size:24px"><strong>Cronograma licitacion</strong></td>
        </tr>
      <tr>
        <td width="41" height="24" align="right">Fecha apetura</td>
        <td width="166" id="fechaapertura"><label for="textfield"></label></td>
        <td width="57" align="right">Manifestaciones interes</td>
        <td width="144" id="minteresse"><label for="textfield2"></label></td>
        <td width="121" align="right">Observaciones pre pliegos</td>
        <td width="158" id="oprepliegos"><label for="textfield3"></label></td>
        </tr>
      <tr>
        <td height="24" align="right">Respuesta observaciones</td>
        <td id="robservaciones"><label for="textfield4"></label></td>
        <td align="right">Pliegos definitivos</td>
        <td id="pdefinitivos"><label for="textfield5"></label></td>
        <td align="right">observaciones pliegos definitivos</td>
        <td id="opdefinitivos"><label for="textfield6"></label></td>
        </tr>
      <tr>
        <td height="24" align="right">Fecha cierre:</td>
        <td id="fechacierre">&nbsp;</td>
        <td align="right">Informes de evaluacion</td>
        <td id="ievaluacion"><label for="textfield7"></label></td>
        <td align="right">Observaciones evaluacion</td>
        <td id="oevaluacion"><label for="textfield8"></label></td>
        </tr>
      <tr>
        <td height="24" align="right">Respuesta observaciones evaluacion</td>
        <td id="roevaluacion"><label for="textfield9"></label></td>
        <td align="right">Subsanacion requisitos habilitantes</td>
        <td id="srhabilitantes"><label for="textfield11"></label>
          <label for="textfield10"></label></td>
        <td align="right">Adjudicacion</td>
        <td id="adjudicacion">&nbsp;</td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td>Comentario:</td>
    <td id="comentario">&nbsp;</td>
  </tr>
  <tr>
    <td>Dar por enterado:</td>
    <td><textarea name="obs" id="obs" cols="35" rows="2" required="required" placeholder="Digite aqui comentarios..."></textarea></td>
  </tr>
  </table>
<br />
<input type="image" src="../virtualmax/imagenes/guardar.png" name="button" id="button" value="Enviar" height="44" width="128"/>


</form>


</div>
<?php 
}
?>


<?php 
	if($res_checklist[0]>0){
?>
<div id="carga"></div>
<div id="menu_novedades2" hidden="" title="Revise y Autorice el Checklist..." align="center" style="background-color:#FFC2A6; font-size:14px" >
<form action="" method="post">
<table width="715" border="1" align="center" cellpadding="0" cellspacing="0" bgcolor="">
  <tr>
    <td colspan="3" align="center">Seleccione una Licitacion: 
      <?php 
	  $consutla2=$conexion->sentenciasql("
	  SELECT llv.`entidad`,mn.`idLicitacionesRegistroVisitas`
FROM licitaciones_links_visitas llv,
     licitaciones_links_registro_visitas llrv,
     menu_novedades mn
WHERE 
      llv.`id`=llrv.idlinksvisitas AND
      llrv.id=mn.`idLicitacionesRegistroVisitas` AND
      mn.`estado`=0 
	  AND mn.tipo_novedad=2
  GROUP BY llv.`entidad`,mn.`idLicitacionesRegistroVisitas`
	  
	  ");
	  
	  ?>
      <select name="select" id="select" required>
        <option value="">Seleccione Entidad con licitacion... </option>
        <?php 
		while($resli=mysql_fetch_array($consutla2)){
		$entidad=substr($resli['entidad'],0,40);
		?>
        <option value="<?php echo $resli['idLicitacionesRegistroVisitas']?>"><?php echo $entidad.'('.$resli['idLicitacionesRegistroVisitas'].')'?></option>
        <?php }?>
        
      </select></td>
  </tr>
  <tr>
    <td colspan="3" align="center">DOCUMENTOS DE ORDEN LEGAL</td>
  </tr>
  <tr>
    <td colspan="3">1. Carta manifestación de interés (Si aplica).<br />
      2. Fotocopia Representante legal<br />
      3. Certificado de Existencia y representación legal.<br />
      4. Certificación de parafiscales expedida por la Revisora Fiscal<br />
      5. Planillas del SOI</td>
    </tr>
  <tr>
    <td colspan="3" align="center">DOCUMENTOS DE ORDEN FINANCIERO</td>
    </tr>
  <tr>
    <td colspan="3">1. Carta manifestación de interés (Si aplica).<br />
2. Fotocopia Representante legal<br />
3. Certificado de Existencia y representación legal.<br />
4. Certificación de parafiscales expedida por la Revisora Fiscal<br />
5. Planillas del SOI</td>
    </tr>
  <tr>
    <td colspan="3" align="center">DOCUMENTOS DE ORDEN TECNICO</td>
    </tr>
  <tr>
    <td colspan="3">1. Carta manifestación de interés (Si aplica).<br />
2. Fotocopia Representante legal<br />
3. Certificado de Existencia y representación legal.<br />
4. Certificación de parafiscales expedida por la Revisora Fiscal<br />
5. Planillas del SOI</td>
    </tr>
  <tr>
    <td colspan="3" align="center">DOCUMENTOS DE ORDEN ECONOMICO</td>
    </tr>
  <tr>
    <td colspan="3">1. Carta manifestación de interés (Si aplica).<br />
2. Fotocopia Representante legal<br />
3. Certificado de Existencia y representación legal.<br />
4. Certificación de parafiscales expedida por la Revisora Fiscal<br />
5. Planillas del SOI</td>
    </tr>
  <tr>
    <td width="348">Digite sus comentarios:</td>
    <td width="477"><textarea name="obs2" id="obs2" cols="45" rows="3" required="required" placeholder="Digite aqui comentarios..."></textarea></td>
    <td width="477"><label for="autoriza"></label>
      <select name="autoriza" id="autoriza" required>
        <option value="">Seleccione una opcion</option>
        <option value="1">Cumplimos</option>
        <option value="2">Cumplimos Parcial</option>
        <option value="3">No Cumplimos</option>
      </select></td>
  </tr>
  </table>
<br />
<input type="image" src="../virtualmax/imagenes/guardar.png" name="button" id="button" value="Enviar" height="44" width="128"/>

</form>


</div>
<?php 
}
?>


<audio id="sonido">
    <source src="audios/3bells.mp3"> </source>
    <source src="3bells.mp3"> </source>
</audio>
<audio id="ingresoContraentrega">
	<source src="audios/ingresos2.MP3" />
</audio>
<!--<audio id="mesaAyuda">
	<source src="../virtualmax/imagenes/sound/helpdesk.mp3"/>
</audio>-->
<table width="960" border="0" align="center" cellpadding="0" cellspacing="0">
 <tr>
  <th colspan="6" scope="col">
  <a href="http://www.mediqboy.com/">
  <img src="imagenes/cabezote.jpg" width="960" height="63" border="0" />
  </a>
  </th>
 </tr>
 <tr>
  <td colspan="6" align="center"><?php
if($pendienteContraE > 0 && $idLogIn == 5000)
{
	?>
	<script language="javascript">
	ingresoContraentrega.play();
    open('vista/ingresoProveedores/listaIngresos.php','pendientes','width=1100,height=450,toolbar=No,location= No,scrollbars=No,status=No,resizable=No,fullscreen =No');
    </script> 
    <?php
}
?>

 <table width="900" border="0" align="center" cellpadding="0" cellspacing="3">
 <tr>
  <th scope="col">
  <a href="http://www.mediqboy.com/">
  <img src="imagenes/inicio.png" width="145" height="33" border="0" />
  </a>
  </th>
  <th scope="col">
  <a href="http://www.mediqboy.com/index.php/nosotros">
  <img src="imagenes/nosotros.png" width="145" height="33" border="0" />
  </a>
  </th>
  <th scope="col">
  <a href="http://www.mediqboy.com/index.php/tienda-virtual">
  <img src="imagenes/tienda-virtual.png" width="145" height="33" border="0" />
  </a>
  </th>
  <th scope="col">
  <a href="http://www.mediqboy.com/index.php/zona-clientes">
  <img src="imagenes/zona-clientes.png" width="145" height="33" border="0" />
  </a>
  </th>
  <th scope="col">
  <a href="http://www.mediqboy.com/index.php/zona-proveedores">
  <img src="imagenes/zona-proveedores.png" width="145" height="33" border="0" />
  </a>
  </th>
  <th scope="col">
  <a href="http://www.mediqboy.com/index.php/contacto">
  <img src="imagenes/contacto.png" width="145" height="33" border="0" />
  </a>
  </th>
 </tr>
 <tr style="color:#000"> 
 <?php
 //revisa si el funcionario tiene novedades por ver en aplicativos
 $sql="SELECT count(*) conteo FROM que_nuevo q WHERE NOT EXISTS (SELECT id_que_nuevo FROM que_nuevo_funcionarios f WHERE q.`id`=f.`id_que_nuevo` and f.idFuncionario=".$_SESSION['documento'].")";
  $result_1 = $conexion->sentenciasql($sql);
  if(!$result_1){$conteo=0;}
  else {$objeto=mysql_fetch_array($result_1);$conteo = $objeto['conteo'];}
  
 ?>
  <th align="center"><?php if($conteo==0){?>
    What's New
      <?php }?></th>
  <th rowspan="2" align="center">&nbsp;</th>
  <th colspan="2" rowspan="2" align="center"><br />        
  <img src="imagenes/ftperfil2017/<?php echo $_SESSION['documento'].'.png';?>" width="100" height="104" /><br />
  
  <font color="#FF0000">BIENVENIDO:<br /><?php echo $_SESSION['Nombre'];
   ?></font></th>
  <th colspan="2" rowspan="2" align="left">
  <table align="left">
   <tr>
   <?php
   /*if($totalSolPendientes > 0 && ($idRolTarea == 5 || $idRolTarea == 51))
   {
   ?>	 
    <script language="javascript">
	mesaAyuda.play();
    </script> 
   <?php
   }*/
 
	?>
     <th>
      <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=2" target="_blank"><img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad" /></a>
   <form name="enviar" action="../virtualmax/mesaAyuda/vista/opciones.php" method="post" target="_blank">
   <input type="submit" style="width:40px; height:40px; background-image:url(../virtualmax/imagenes/iconos/campana.png); background-color:transparent; cursor:pointer; background-size:cover; border:none; color:#E10000; font-size:20px;" id="numeroNotificacion" value="" title="MESA DE AYUDA."/>
  
   </form>
     </th>
 
    <th style="background-image:url(imagenes/notes.png); background-size:cover; background-position:center; color:#F00; font-size:20px" width="45" align="center" valign="middle">
    <a href="vista/novedadesOperativos/listaNovedades.php" target="_blank" title="Novedades" style="color:#F00">
    <?php 
	if($novedadSinFinalizar < 1 && $idRolTarea > 20)
	{
	?>
		<img src="imagenes/new-icon.png" width="20" height="20" title="Nueva Novedad"/>
	<?php
	}else
	{
		echo $novedadSinFinalizar;
	}
	?>
    </a>
    </th>
    <td>
    <a href="../oc/cartera/wx.php" target="_blank">
    <img src="imagenes/encuesta.png" width="45" height="45" title="Actualización WXM"/>
    </a>
    </td>
    <td>
    <a href="imagenes/MANUALPLANTATELEFONICA.pdf" target="_blank"><img src="../virtualmax/imagenes/phone.png" width="45" height="45" title="Directorio Telefonico"/></a>
    </td>
    <td>
    <a href="Vista/usuario/clave.php" target="_blank">
    <img src="imagenes/key.png" width="45" height="45" title="Cambiar Contraseña"/>
    </a>
    </td>
    <td>
    <a href="destruir.php">
    <img src="imagenes/salir.png" width="40" height="40" title="Cerrar Sesión"/>
    </a>
    </td>
   </tr>
  </table>
  </th>
 </tr>
 <tr style="color:#000">
   <th height="70" align="center" <?php if($conteo>0){?>style="background-image:url(../virtualmax/imagenes/iconos/boton_what.png); background-repeat:no-repeat; background-position:center; background-size:contain; color:#FFF" title="Existen: <?php echo $conteo;?> actualizaciones en la plataforma"<?php }?>><?php if($conteo==0){?><a href="../virtualmax/que_nuevo/vista/index.php" target="_blank" style="color:#FFF"><?php echo $conteo;?></a><?php }?></th>
 </tr>
 <tr>
  <th colspan="6" align="center">
  <table cellpadding="0" cellspacing="0" width="815" height="123" border="1" style="border:2px dashed #CCC;">
   <tr>
    <th colspan="11" scope="col" bgcolor="#0066CC"><font color="#FFFFFF">CONTROL TAREAS</font></th>
   </tr>
   <tr style="color:#000">
    <td>Pendientes</td>
    <td align="center">En Tramite</td>
    <td>Finalizadas</td>
    <td>Reservadas</td>
    <td width="86">Reasignadas</td>
    <td width="71">Calendario</td>
    <td width="71"><?php
	if ($_SESSION['idRolTarea'] < 20)
	{
    ?>
    Delegadas
    <?php
	}
	?>
    </td>
    <td width="101" align="center"><?php
	if ($_SESSION['idRolTarea'] < 20 or $_SESSION['idRolTarea'] == 30)
	{
    ?>
    Tarea Nueva<a href="Vista/tareas/ver.php" target="_blank">
    <?php
	}
	?>
    </a>
    </td>
    <td width="74" align="center"><?php
	if ($_SESSION['idRolTarea'] < 20)
	{
    ?>
    Ver Todas<a href="Vista/tareas/ver.php" target="_blank">
    <?php
	}
	?>
    </a>
    </td>
    <td width="74" align="center"><?php
	if ($_SESSION['idRolTarea'] < 20)
	{
    ?>
    Estad&iacute;stico<a href="Vista/tareas/ver.php" target="_blank">
    <?php
	}
	?>
    </a>
    </td>
    <td>Circulares</td>
   </tr>
   <tr>
    <th width="79" bgcolor="#FF0000"> 
	<a href="Vista/tareas/pendientes.php?id=<?php echo $idLogIn;?>&menu=1" title="Tareas Pendientes">
		<?php echo $tPendiente; ?>
	    <?php if($tPendiente>0 || $tareasRed['Pendiente'] > 0 || $tareasCierre > 0){ ?>	 
	    	<script language="javascript">
				sonido.play();
	    		open('vista/tareas/alertas.php','pendientes','width=1100,height=450,toolbar=No,location=No,scrollbars=No,status=No,resizable=No,fullscreen =No');
	    	</script> 
		<?php } ?>
    </a>
	</th>
    <th width="83">
	<a href="Vista/tareas/activas.php?id=<?php echo $idLogIn;?>&menu=1" title="Tareas en Tramite" target="_blank">
	<?php echo $tActiva; ?>
    </a>
    </th>
    <th width="74" bgcolor="#00CC00">
    <a href="Vista/tareas/finalizadas.php?id=<?php echo $idLogIn;?>&menu=1" title="Tareas Finalizadas" target="_blank">
	<?php echo $tFinalizada; ?>
    </a>
    </th>
    <th width="80" bgcolor="#CCCCCC" target="_blank">
    <a href="Vista/tareas/reservadas.php?id=<?php echo $idLogIn;?>&menu=1" title="Tareas Reservadas" target="_blank">
	<?php echo $tReservada; ?>
    </a>
    </th>
    <th>
    <a href="Vista/tareas/redireccionadas.php?id=<?php echo $idLogIn;?>&menu=1" title="Tareas Reasignadas" target="_blank">
    <?php echo $tRedireccionadas;?>
    </a>
    </th>
    <th align="center">
    <?php 
	
	
	
	?>
    
    <a href="../virtualmax/calendario" target="_blank"><img src="../virtualmax/imagenes/iconos/calender.png" width="50" height="50" title="Cree recordatorios para tus novedades" <?php if($calendatio[0]<>0){ ?>id="recor"<?php }?>/></a></th>
    <th align="center">
    <?php
	if ($_SESSION['idRolTarea'] < 30)
	{
    ?>
    <a href="Vista/tareas/asigno.php">
    Ver
    </a>
    <?php
	}
	?>
    </th>
    <th align="center"><?php
	if ($_SESSION['idRolTarea'] < 30 || $_SESSION['idRolTarea'] == 30)
	{
    ?>
    <a href="Vista/decicion.php" target="_blank">
    <img src="imagenes/new-icon.png" width="40" height="40" title="Asignar Tarea"/>
    </a>
    <a href="Vista/tareas/ver.php" target="_blank">
    <?php
	}
	?>
    </a>
    </th>
    <th align="center">
    <a href="Vista/tareas/ver.php" target="_blank">
    <?php
	if ($_SESSION['idRolTarea'] < 30)
	{
    ?>
    <img src="imagenes/CHECK.png" width="40" height="40" title="Listado de Tareas"/>
    <?php
	}
	?>
    </a>
    </th>
    <th align="center">
    <a href="Vista/tareas/fechaEstadistico.php" target="_blank">
    <?php
	if ($_SESSION['idRolTarea'] < 30)
	{
    ?>
    <img src="imagenes/statistics_64.png" width="40" height="40" title="Listado de Tareas"/>
    <?php
	}
	?>
    </a>
    </th>
    <?php
    	$numeroCircular=0;
    	if($idLogIn == 49){
	     include('../virtualmax/circular2.0/dao/dao_circular.php');
	     $daoCircularPendiente = new dao_correspondencia();
	     $cnsNumcircular = $daoCircularPendiente->numeroCircularesPendientes();
	     if(mysql_num_rows($cnsNumcircular)>0){
	     	     $resNumcircular = mysql_fetch_array($cnsNumcircular);
	     		 $numeroCircular = $resNumcircular['nCircular'];
	     ?>
	     <audio preload autoplay src="../virtualmax/circular2.0/control/SonidoCircular.mp3"></audio>
	     <?php
	     }
	     }

     ?>
    <th title="Circulares Pendientes"><a href="../virtualmax/circular2.0/vista/index.php" target="blank"><?php echo $numeroCircular;?></a><th>
   </tr>
  </table>
  <link rel="stylesheet" type="text/css" href="engine1/style.css" />

  </th>
 </tr>
 <tr>
  <th colspan="6" align="center">
  </th>      
 </tr>
</table>

<?php
/* inicio amigo secreto
$conexiona=new MySQL("bodega");
$regalo="";
$sqlamigo=$conexiona->sentenciasql("select idamigosecreto from amigo_secreto where idusuario=".$idLogIn."")or die (mysql_error())or die(mysql_error());
$resamigo=mysql_fetch_array($sqlamigo);

if($resamigo[0]!=NULL){

$sqlfin=$conexiona->sentenciasql("select nombre from usuario where id=".$resamigo[0]."")or die(mysql_error());
$amigo=mysql_fetch_array($sqlfin);
$sqlregalo=$conexiona->sentenciasql("select regalodeseado from amigo_secreto where idusuario=".$resamigo[0]."")or die (mysql_error());
$resregalos=mysql_fetch_array($sqlregalo);
//echo '<strong>Tu amigo(a) secreto le gustaría que le regalaran:</br>'.$regalo=$resregalos[0].'</strong>';

}
 

if($resregalos[0]==NULL and $idLogIn!=84){
?>
	<a href="vista/amorAmistad/amigoSecreto.php">
		<img src="imagenes/amor.jpg" width="112" height="95"/></a>
		</br>
<?php 

	 }else{
		 
		
$ami=$amigo[0];
$reg=$resregalos[0];	 
*/		 
?>
<!--
 	<form id="form1" name="form1" method="post" action="veramigo.php" >
		<input name="amigo" type="hidden" value="<?php echo $ami;?>"/>
		<input name="regalo" type="hidden" value="<?php echo $reg;?>"/>
		<input type="submit" name="button2" width="90" height="90"  title="click para ver amigo"/>
	</form>
-->	
<?php
 
		 //}// Fin amigo secreto

?>
<h1><strong>
  <font color="#FF0000">M</font><font color="#000000">edicinal Virtual  Co</font><FONT color="#FF0000">M</FONT><font color="#000000">pany</font> - <FONT color="#FF0000">M</FONT><font color="#000">ediqboy</font>
  <u></u></strong></h1>
    <div id="TabbedPanels1" class="TabbedPanels">
      <ul class="TabbedPanelsTabGroup">
      <li class="TabbedPanelsTab" tabindex="0">Bodega</li>
      <li class="TabbedPanelsTab" tabindex="0">Cartera</li>
      <li class="TabbedPanelsTab" tabindex="0">Contabilidad</li>
      <li class="TabbedPanelsTab" tabindex="0">Compras</li>
      <li class="TabbedPanelsTab" tabindex="0">Juridica</li>
	  <li class="TabbedPanelsTab" tabindex="0">RR-HH</li>
      <li class="TabbedPanelsTab" tabindex="0">Licitaciones</li>
      <li class="TabbedPanelsTab" tabindex="0">Guarda</li>      
      <li class="TabbedPanelsTab" tabindex="0">Droguerias</li>      
      <li class="TabbedPanelsTab" tabindex="0">Tesoreria</li>            
      <li class="TabbedPanelsTab" tabindex="0">Ventas</li>            
<?php if(($_SESSION['Rol']=="10") ||($_SESSION['Rol']=="15") ||($_SESSION['Id']==50)){ ?> 

      <li class="TabbedPanelsTab" tabindex="0">Gerencia</li>
<?php }?>      
      <li class="TabbedPanelsTab" tabindex="0">Conductores-Domiciliarios</li>
      <li class="TabbedPanelsTab" tabindex="0">TODOS</li>
      <li class="TabbedPanelsTab" tabindex="0">Diseño</li>
      <li class="TabbedPanelsTab" tabindex="0">Nuevo</li>
   </ul>


<div class="TabbedPanelsContentGroup">

      <div class="TabbedPanelsContent">
        <table class="tablas" cellspacing="20">
            <caption>DIR BODEGA BODEGA</caption>

          <tr>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=33" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=33" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/bodega/rutas/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/ruta.mediqboy.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono"><a href="../bodega/inicio.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/control.bodega.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=118" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=118" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../bodega/rotulos/cargue.php">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/cargue.caminon.png" class="tdimagen"/></a>
<A href="../qyr/index.php" target="_blank"></a></td>
            <td align="center" class="tdicono"><a href="../oc/cartera/tipo_cartera.php" target="_blank"><img src="../virtualmax/imagenes/iconosestandar/BODEGA/cartera.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=64" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=64" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../bodega/surtidor/vista/" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/surtidor.bodega.png" class="tdimagen" /></a></td>
            </tr>
            <td align="center" class="tdicono">
            	<!--<a href="vista/Vehiculos/opcionesVehiculos.php" target="_blank" hidden="">
            		<img src="../virtualmax/imagenes/iconos estandar/mantenimiento de vehiculos.png" width="57" height="57" hidden=""/>
            	</a>-->
                <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=126" target="_blank">
                <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
                </a>             
                <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=126" target="_blank" title="Click para mesa de ayuda">
            	<img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            	<a href="../virtualmax/MantenimientoVehiculos/vista/index.php" target="_blank" >
            		<img src="../virtualmax/imagenes/iconosestandar/BODEGA/mantenimientoVehiculos.png" class="tdimagen" />
            	</a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=49" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=49" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/auditorias_wx/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/auiditoria.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono"><a href="../oc/clientes/actdatos.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/actualizacion.datos.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=67" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=67" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
            <a href="../bodega/menuresolucion.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/resolucion.clientes.png"  class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=68" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=68" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../../bodega/asignacion/restaurar.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/restaurar.separador.png" class="tdimagen"/></a></td>
          </tr>
          <tr>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=69" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=69" target="_blank">
            <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad"/></a>
            <a href="../virtualmax/cotizacion/vista/kardex.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/kardex.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono"><a href="http://www.tusatelital.com" target="_blank"></a><a href="../virtualmax/item/vista/negativos.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/bodega.1.2.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=99" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=99" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/item/vista/items_error.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/auditoria.productos.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=109" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=109" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"></a>
            <a href="../virtualmax/bodega/contenedor/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/contenedores.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../bodega/inventario/granel/seleccionar.php" target="_blank"></a>
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=111" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=111" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../oc/proveedor/revisar_cartera.php" target="_blank"><img src="../virtualmax/imagenes/iconosestandar/BODEGA/actualizacion.proveedor.png" class="tdimagen" /></a></td>
          </tr>
          <tr>
            <td align="center" class="tdicono">
			<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=112" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=112" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/actualizar_info_tecnica/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/invimas.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=113" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=113" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>            
            <a href="../bodega/ingresos/INDEX.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/reporte.mercancia.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=71" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=71" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/temperatura/vista/Menu_temperatura.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/control.temperatura.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=72" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=72" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../bodega/inicionueve.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/bodega.png" alt="" class="tdimagen" /></a></td>
<td align="center" class="tdicono">&nbsp;</td>
            </tr>
<tr>
  <td colspan="5" align="center">AUDITOR, LOGISTICO, POSTVENTA Y SEPARADOR</font></td>
</tr>  

       <tr>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=79" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=79" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../bodega/separador/separador.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/separador.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=80" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=80" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../bodega/auditor/auditor.php " target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/auditor.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=116" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=116" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad"/></a>
            <a href="Vista/libroSalidas/documentos.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/salida.documentos.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=73" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=73" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
              <a href="../bodega/logistica/guias.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/BODEGA/trasnportadora.png" class="tdimagen"/></a>
              </td>
            <td align="center" class="tdicono"><a href="http://mscol.mobilesuitcase.com/general/sitio/login.aspx" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/BODEGA/gps.png" class="tdimagen"/></a></td>
            </tr>
       <tr >
        	<td align="center" class="tdicono" align="center" class="tdicono">
        	<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=" target="_blank">
        	<img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/></a>
        	<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=" target="_blank" title="Click para mesa de ayuda">
        	<img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
          	<a href="../../qyr/conteosusuarios.php" target="_blank">
          	<img src="../virtualmax/imagenes/iconosestandar/BODEGA/conteos.png" class="tdimagen"/></a>
        	</td>
           <td align="center" class="tdicono">
        	<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=128" target="_blank">
        	<img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/></a>
        	<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=128" target="_blank" title="Click para mesa de ayuda">
        	<img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
          	<a href="../virtualmax/sobrestock/vista/reporte_fechas.php" target="_blank">
          	<img src="../virtualmax/imagenes/iconosestandar/bodega/productoVencen.png" class="tdimagen"/></a>	
           </td>
        	<td align="center" class="tdicono"></td>
        	<td align="center" class="tdicono"></td>
        	<td align="center" class="tdicono"></td>
       </tr>
       <tr>
         <td colspan="5" align="center" >RECEPCION DE MERCANCIAS</font></td>
       </tr>
       <tr>
         <td align="center" class="tdicono">
         <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=74" target="_blank">
         <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
         </a>
         <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=74" target="_blank" title="Click para mesa de ayuda">
         <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
         <a href="../bodega/recepcion/INDEX.php" target="_blank">
         <img src="../virtualmax/imagenes/iconosestandar/BODEGA/recepcion.mercancia.png" class="tdimagen"/></a></td>
         <td align="center" class="tdicono"><a href="vista/ingresoProveedores/index.php" target="_blank">
         <img src="../virtualmax/imagenes/iconosestandar/BODEGA/ingreso.mercancia.png" class="tdimagen" /></a></td>
         <td align="center" class="tdicono">
         <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=75" target="_blank">
         <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
         </a>
         <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=75" target="_blank" title="Click para mesa de ayuda">
         <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
         <a href="../oc/invima" target="_blank">
         <img src="../virtualmax/imagenes/iconosestandar/BODEGA/actualizar.cun.png" class="tdimagen" style="border:none" /></a></td>
         <td align="center" class="tdicono">
         <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=31" target="_blank">
         <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/>
         </a>
         <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=31" target="_blank" title="Click para mesa de ayuda">
         <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad"/></a>
         <a href="../oc/barras/actualizar.php" target="_blank">
         <img src="../virtualmax/imagenes/iconosestandar/BODEGA/actualizar.codigo.png" class="tdimagen" />
         </a></td>
        
          <td align="center" class="tdicono"><a href="marca/consulta11.php" target="_blank"><img src="../virtualmax/imagenes/iconosestandar/BODEGA/consulta.png" class="tdimagen" /></a></td>
         
       </tr>
       <tr>
            <td colspan="5" align="center">INGRESOS DE MERCANCIAS</font></strong></td>
          </tr>  

      		 <tr>
            
                <td align="center" class="tdicono">
                <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=76" target="_blank"><img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad"/></a><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=76" target="_blank"><img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
                <a href="../virtualmax/auditorias_wx/vista/ea_valores_correccion.php" target="_blank"><img src="../virtualmax/imagenes/iconosestandar/BODEGA/auditoria.digital.png" class="tdimagen"/></a></td>
                <td align="center" class="tdicono"><a href="../bodega/ingresos/verenviocorreos.php" target="_blank"><img src="../virtualmax/imagenes/iconosestandar/BODEGA/correo.novedad.recepcion.png" class="tdimagen"/></a></td>
                <td align="center" class="tdicono"><a href="../virtualmax/bodega/entradasalmacen/index.php" target="_blank"><img src="../virtualmax/imagenes/iconosestandar/BODEGA/entrada.recepcion.png" class="tdimagen"/></a></td>
               
                <td align="center" class="tdicono">&nbsp;</td>
                <td align="center" class="tdicono">&nbsp;</td>
        	</tr>
        </table>
      </div>
      <div class="TabbedPanelsContent" hidden="">
<table  align="center" class="tablas" cellspacing="20">
	<caption>DEPARTAMENTO DE CARTERA</caption>
    <tr>
      <td align="center" class="tdicono"> 
      <a href="../oc/cartera/tipo_cartera.php" target="_blank">
      <img src="../virtualmax/imagenes/iconosestandar/cartera/cartera.c.png" class="tdimagen"/></a></td>
      <td align="center" class="tdicono">
       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=44" target="_blank">
      <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
      <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=44" target="_blank">
      <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad" title="Mesa de ayuda App"/></a>
      <a href="vista/productos/clientesContrato.php"  target="_blank">
      <img src="../virtualmax/imagenes/iconosestandar/cartera/contratos.png" class="tdimagen" /></a></td>
      <td align="center" class="tdicono">
      <a href="../virtualmax/proveedores/vista/menu_clientes.php" target="_blank">
      <img src="../virtualmax/imagenes/iconosestandar/cartera/clientes.png" class="tdimagen"/></a></td>
     <td class="tdicono"></td>
     <td class="tdicono"></td>  
    </tr>
  </table>       
      </div>
      <div class="TabbedPanelsContent">
        <table  align="center" class="tablas" cellspacing="20">
        <caption>DEPARTAMENTO DE CONTABILIDAD</caption>
          <tr>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=51" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=51" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad"/></a>
            <a href=" ../virtualmax/licitaciones/vista/index.php" target="_blank" >
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/licitacionescheck.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=49" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=49" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad"/></a>
            <a href="../oc/clientes/actdatos.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/actualizacionDatos.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=47" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=47" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad"/></a>
            <a href="droguerias/vista/seleccionar.php"  target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/cuadresDroguerias.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=48" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a><a href="../virtualmax/rbac/control/app_seguridad.php?id_app=48" target="_blank"></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=48" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="cajamenor/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/cajamenorDrogueria.png"  class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/auditorias_wx/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/auditoriaswx.png" class="tdimagen"/></a></td>
            
          </tr>
          <tr>
            <td align="center" class="tdicono"><a href="/oc/auditorias/" target="_blank">
            <a href="" hidden="">	<img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/auditorias.png"  class="tdimagen" hidden="" /></a>
            </td>
            <td align="center" class="tdicono"><a href="vista/tarjetaEfipago/opcionesTarjeta.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/targetasEfipago.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono"><a href="../oc/cartera/tipo_cartera.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/cartera.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=46" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=46" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad"/></a>
            <a href="../virtualmax/presupuesto/" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/presupuesto.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=4" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png" class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=4" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad"/></a><br />
            <a href="../../virtualmax/proveedores/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/proveedores.png" class="tdimagen"/></a></td>
          </tr>
          <tr>

            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=20" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=20" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad"/></a>
            <a href="../virtualmax/ventas/bonificado/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/bonificacion.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=45" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=45" title="Click para mesa de ayuda" target="_blank">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
            <a href="../../virtualmax/contabilidad/bancos/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/bancoswx.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../encuesta_ext/login.php" target="_blank">
            <img src="imagenes/salesman.png" alt="" width="64" height="64" style="border:none" hidden=""/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=45" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=45" title="Click para mesa de ayuda" target="_blank">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
            <a href="activos_fijos/menu_principal.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/CONTABILIDAD/activos.png" class="tdimagen"/></a></td>
            <td align="center" class="tdicono">&nbsp;</td>
          </tr>
          <tr></tr>
        </table>
      </div>      
      <div class="TabbedPanelsContent">
        <table align="center" class="tablas" cellspacing="20">
            <caption>COMPRAS</caption>
          <tr>
            <td align="center"  class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=106" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=106" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>            
            <a href="../virtualmax/proveedores/vista/listados.php" target="_blank"><img src="../virtualmax/imagenes/iconosestandar/compras/correo.masivo.png" class="tdimagen"></a></td>
            <td align="center"  class="tdicono"><a href="../virtualmax/proveedores/vista/menu_clientes.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/proveedores.png" class="tdimagen" /></a></td>
            <td align="center"  class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=105" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=105" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../oc/subasta/admon.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/subasta.virtual.png" class="tdimagen" /></a></td>
            <td align="center"  class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=54" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=54" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="ventas/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/ventas.png" class="tdimagen" /></a></td>
            <td align="center"  class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=96" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=96" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../bodega/recepcion/rnovedades.php" target="_blank" >
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/devolucion.proveedor.png" class="tdimagen" /></a></td>
          </tr>
          <tr>
           
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=90" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=90" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../bodega/informaciontecnica/fichasfin.php">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/fichas.tecnicas.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=107" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=107" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/ordencompra/vista/ordenes.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/seguimiento.oc.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=108" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=108" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="compras/diferencias.php" target="_blank" >
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/faltante.orden.compra.png" class="tdimagen" /></a></td>
            
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=51" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=51" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/licitaciones/vista/index.php" target="_blank" >
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/licitaciones.1.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=4" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=4" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/proveedores/vista/index.php" target="_blank" >
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/proveedores.1.png" class="tdimagen"/></a></td>
          </tr>
          <tr>
          
            
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=52" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=52" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/item/vista/solicitudes_ventas.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/productos.nuevos.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=110" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=110" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../bodega/ordencompra/verlista.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/precios.2015.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=5" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=5" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/sobrestock/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/sobrestock.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=99" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=99" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/item/vista/negativos.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/bodega.1.2.1.png"  class="tdimagen"/></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=53" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=53" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="../virtualmax/compras/sugerido/vista/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/sugeridos.ordenes.png" class="tdimagen" /></a></td>
             
          </tr>
          <tr>
          	<td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=109" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=109" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a> 
            <a href="../virtualmax/item/vista/items_error.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/item.novedades.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono"><a href="../virtualmax/sobrestock/vista/audProductosPorVencer.php" target="blank">Audita Productos</a></td>
            <td align="center" class="tdicono"><a href="vista/faltantes" target="_blank" hidden="">
            <img src="imagenes/FALTANTE.png" class="tdimagen" /></a></td>
            

            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=109" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>             
            </a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=109" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a> 
            <a href="../virtualmax/cotizacion/vista/reporteProductos1.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/COMPRAS/perdida.png" class="tdimagen" /></a></td>
          </tr>         
        </table>
      </div>
      <div class="TabbedPanelsContent">
        <table align="center" class="tablas" cellspacing="20">
          
            <caption><font color="#FFFFFF">DEPARTAMENTO DE JURIDICA</font></caption>
           
          <tr>
            <td align="center" class="tdicono"><a href="../oc/cartera/tipo_cartera.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/JURIDICA/cartera.1..png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono"><a href="../virtualmax/proveedores/vista/menu_clientes.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/JURIDICA/clientes.1.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono">
            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=44" target="_blank">
            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=44" target="_blank" title="Click para mesa de ayuda">
            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
            <a href="vista/productos/clientesContrato.php"  target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/JURIDICA/contratos.1.png" class="tdimagen" /></a></td>
            <td align="center" class="tdicono"></td>
            <td align="center" class="tdicono"></td>
          </tr>
        </table>
      </div>                   
       <div class="TabbedPanelsContent">
         <table align="center" class="tablas" cellspacing="20" >
           
             <caption><font color="#FFFFFF">DEPARTAMENTO DE RECURSOS HUMANOS</font></caption>

           <tr>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=25" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=25" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             <a href="../virtualmax/rh_documentos/index.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/contratos.documentos.png" class="tdimagen" /></a></td>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=27" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>
             </a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=27" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             
             <a href="vista/vacaciones/funcionarios.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/vacaciones.png" class="tdimagen" /></a></td>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=28" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=28" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             <a href="../bodega/estadistica/estfinal.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/estadistico.separado.png" class="tdimagen" /></a></td>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=26" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=26" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             <a href="../virtualmax/ingresopersonal/vista/reporte1.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/reporte.ingreso.personal.png" class="tdimagen" /></a></td>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=114" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=114" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
             <a href="../virtualmax/contabilidad/nomina/novedades.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/novedades.nomina.png" class="tdimagen" /></a></td>
           </tr>
           <tr>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=29" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=29" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             <a href="../virtualmax/notificaciones/index.html" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/notificaciones.email.png" class="tdimagen" /></a></td>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=21" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=21" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             <a href="../virtualmax/cumplimientocronogramas/vista/index.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/evaluacion.cronograma.png" class="tdimagen"/></a></td>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=22" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=22" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             <a href="../virtualmax/rbac/vista/index.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/adm.usuarios.png" class="tdimagen"/></a></td>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=37" target="_blank">
			 <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=37" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             <a href="../virtualmax/ingresopersonal/vista/entrada_rrhh.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/ingreso.personal.png" class="tdimagen" alt="Imagen Ingreso" /></a></td>
             <td align="center" class="tdicono">
             <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=115" target="_blank">
             <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
             <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=115" target="_blank" title="Click para mesa de ayuda">
             <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
             <a href="vista/vacantesExternos/postulados.php" target="_blank">
             <img src="../virtualmax/imagenes/iconosestandar/RR-HH/hojas.vida.png" class="tdimagen" /></a></td>
           </tr>
           <tr>
           	<td align="center" class="tdicono"><a href="../virtualmax/contabilidad/nominap/index.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/RR-HH/nomina.png" class="tdimagen"/>
            </a></td>
           	<td align="center" class="tdicono"><a href="http://mscol.mobilesuitcase.com/general/sitio/login.aspx" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/RR-HH/gps..png" class="tdimagen" title="Usuario: AdminEco
Clave: se002@"/></a></td>
           	<td align="center" class="tdicono"><a href="firma_cliente/guiasfirmadas.php" target="_blank">
            <img src="../virtualmax/imagenes/iconosestandar/RR-HH/reporte.firmas.png" class="tdimagen"/></a></td>
           	<td align="center" class="tdicono">
	            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=124" target="_blank">
	            <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>
	            </a>
	            <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=124" target="_blank" title="Click para mesa de ayuda">
	            <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
	            <a href="../virtualmax/circular2.0/vista/" target="_blank">
	            <img src="../virtualmax/imagenes/iconosestandar/RR-HH/circulares.internas.png" class="tdimagen"/>
	            </a>
	        </td>
           	<td align="center" class="tdicono">
           		<a href="../virtualmax/virtualmanager/index.php" target="_blank">
	            	<img src="../virtualmax/imagenes/iconos/manager1.png" height="128" width="128" class="tdimagen"/>
	            </a>
	        </td>
           </tr>
         </table>
       </div>   
        <div class="TabbedPanelsContent">
          <table align="center" class="tablas" cellspacing="20" >  
              <caption>DEPARTAMENTO DE LICITACIONES</caption>
           <tr>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=44" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=44" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>			  
              <a href="vista/productos/clientesContrato.php"  target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/LICITACIONES/contratos.1.1.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=77" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=77" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
              <a href="../licitaciones/index_info.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/LICITACIONES/revisar.contratos.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=51" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=51" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
              
              <a href="../virtualmax/licitaciones/vista/index.php" target="_blank" >
              <img src="../virtualmax/imagenes/iconosestandar/LICITACIONES/licitaciones.check.list.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=54" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>
              </a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=54" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
              <a href="ventas/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/LICITACIONES/estadistico.ventas.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono"><a href="../virtualmax/sismed/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/LICITACIONES/soporte.sismed.png" class="tdimagen"/></a></td>
            </tr>
            <tr>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=63" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>
              </a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=63" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
              <a href="polizas/vista/ver.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/LICITACIONES/polizas.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">&nbsp;</td>
              <td align="center" class="tdicono">&nbsp;</td>
              <td align="center" class="tdicono">&nbsp;</td>
              <td align="center" class="tdicono">&nbsp;</td>
            </tr>
          </table>
        </div>    
         <div class="TabbedPanelsContent">
           <table align="center" class="tablas" cellspacing="20" >
               <caption>SEGURIDAD</caption>
             <tr>
               <td class="tdicono" align="center">
               <a href="Vista/salidaVehiculos/opciones.php" target="_blank"> 
               <img src="../virtualmax/imagenes/iconosestandar/GUARDA/salida.vehiculos.png" class="tdimagen" /> </a></td>
               <td class="tdicono" align="center">
               <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=116" target="_blank">
               <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
               <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=116" target="_blank" title="Click para mesa de ayuda">
               <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
               <a href="Vista/libroSalidas/documentos.php" target="_blank"> 
               <img src="../virtualmax/imagenes/iconosestandar/GUARDA/salida.documentos.png" class="tdimagen" /></a></td>
               <td class="tdicono" align="center">
               <a href="http://mscol.mobilesuitcase.com/general/sitio/login.aspx" target="_blank" title="Usuario: AdminEco<br />
Clave: se002@">
               <img src="../virtualmax/imagenes/iconosestandar/GUARDA/gps.1.1.png" class="tdimagen" /></a></td>
               <td class="tdicono" align="center">
               <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=37" target="_blank">
			   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>
               </a>
               <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=37" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
               <a href="../virtualmax/ingresopersonal/vista/entrada_Funcionario.php" target="_blank">
               <img src="../virtualmax/imagenes/iconosestandar/GUARDA/ingreso.personal.1.png" class="tdimagen"  alt="Imagen Ingreso" /></a></td>
               <td class="tdicono"></td>
             </tr>
           </table>
         </div>    
        <div class="TabbedPanelsContent">
          <table align="center" class="tablas" cellspacing="20" >
            <caption>DROGUERIAS</caption>
            <tr>
              <td align="center" class="tdicono">
              <a href="droguerias/ventasdro/menureporte.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/DROGERIAS/cuadre.cajas.png" class="tdimagen"/></a></td> 
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=97" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>
			  </a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=97" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
              <a href="marca/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/DROGERIAS/invetarios.bodega.png" class="tdimagen"/></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=48" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>	
			  </a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=48" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
              <a href="cajamenor/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/DROGERIAS/caja.menor.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=71" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>
			  </a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=71" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
              <a href="../virtualmax/temperatura/vista/Menu_temperatura.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/DROGERIAS/control.temperatura.1.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=37" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/>
			  </a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=37" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
              <a href="../virtualmax/ingresopersonal/vista/entrada_Funcionario.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/DROGERIAS/ingreso.personal.png" class="tdimagen" alt="Imagen Ingreso" /></a></td>
            </tr>
            <tr>
              <td align="center" class="tdicono">
              <a href="droguerias/callcenter/pedidosr.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/DROGERIAS/call.center.png" class="tdimagen" /></a></td>
            </tr>
          </table>           
        </div>    
            <div class="TabbedPanelsContent">
              <table align="center" class="tablas" cellspacing="20" >
                <caption>DEPARTAMENTO DE TESORERIA</caption>
                <tr>
                  <td  align="center" class="tdicono" >
                  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=4" target="_blank">
				  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
                  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=4" target="_blank" title="Click para mesa de ayuda">
                  <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                  <a href="../../virtualmax/proveedores/vista/index.php" target="_blank">
                  <img src="../virtualmax/imagenes/iconosestandar/TESORERIA/proveedores.1.1.png" class="tdimagen" /></a></td>
                  <td  align="center" class="tdicono" >
                  <a href="vista/ingresoProveedores/index.php" target="_blank">
                  <img src="../virtualmax/imagenes/iconosestandar/TESORERIA/ingreso.mercancia.1.png" class="tdimagen" /></a></td>
                  <td  align="center" class="tdicono" >
				  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=40" target="_blank">
				  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/> </a>
				  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=40" target="_blank" title="Click para mesa de ayuda">
				  <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                  <a href="../oc/cartera/cartera_contado.php" target="_blank"><img src="../virtualmax/imagenes/iconosestandar/TESORERIA/carteraContado.png" class="tdimagen" /></a></td>
                  <td  align="center" class="tdicono" >
				  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=73" target="_blank">
				  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=73" target="_blank" title="Click para mesa de ayuda">
                  <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
             	  <a href="../bodega/logistica/guias.php" target="_blank">
            	  <img src="../virtualmax/imagenes/iconosestandar/TESORERIA/trasnportadora.1.png" class="tdimagen" /></a></td>
                  <td  align="center" class="tdicono" >
				  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=104" target="_blank">
				  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
				  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=104" target="_blank" title="Click para mesa de ayuda">
                  <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
             	  <a href="../virtualmax/arqueoCajaMenor/vista/listadoReembolsos.php" target="_blank">
             	  <img src="../virtualmax/imagenes/iconosestandar/TESORERIA/reembolso.png" class="tdimagen" /></a>
                  </td>
                </tr>
				<tr>
                    <td align="center" class="tdicono">
					<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=45" target="_blank">
					<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
					<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=45" target="_blank" title="Click para mesa de ayuda">
					<img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                    <a href="../../virtualmax/contabilidad/bancos/vista/index.php" target="_blank">
                    <img src="../virtualmax/imagenes/iconosestandar/TESORERIA/banco.wx..png" class="tdimagen" /></a></td>
                    <td align="center" class="tdicono">
					<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=82" target="_blank">
					<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
					<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=82" target="_blank" title="Click para mesa de ayuda">
					<img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                    <a href="/oc/auditorias/fa/" target="_blank">
                    <img src="../virtualmax/imagenes/iconosestandar/TESORERIA/auditar.png" class="tdimagen" /></a></td>
                    <td align="center" class="tdicono">
						<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=127" target="_blank">
						<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
						<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=127" target="_blank" title="Click para mesa de ayuda">
						<img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
	                    <a href="../virtualmax/controlpagosBancarios/vista/index.php" target="blank">
	                    <img src="../virtualmax/imagenes/iconosestandar/TESORERIA/pago.proveedores.png"  class="tdimagen"/></a>
                    </td>
                    <td align="center" class="tdicono">
                    	<a href="../virtualmax/Proveedores/vista/tipo_proveedor.php" target="blank">
	                    	<img src="../virtualmax/imagenes/iconos/manager1.png" height="128" width="128"  class="tdimagen"/>
	                    </a>
                    </td>
                    <td align="center" class="tdicono">
						<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=131" target="_blank">
						<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
						<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=131" target="_blank" title="Click para mesa de ayuda">
						<img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
	                    <a href="../mediqboy/centro_dispensacion/menu.php" target="blank">
	                    <img src="../virtualmax/imagenes/iconosestandar/TESORERIA/puntodis.png"  class="tdimagen"/></a>
                    </td>
                </tr>               
              </table>            
            </div>    
                 <div class="TabbedPanelsContent">
                   <table align="center" class="tablas" cellspacing="20" >
                       <caption>DEPARTAMENTO DE VENTAS</caption>
                     <tr>
                       <td align="center" class="tdicono">
                       <a href="../oc/clientes" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/usuarios.png" class="tdimagen"/></a></td>
                       <td align="center" class="tdicono">
                       <a href="http://www.sivicos.gov.co:8080/rs/cum/comprob_cum.jsp"  target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/cums..png" class="tdimagen"   longdesc="http://web.sivicos.gov.co:8080/consultas/consultas/consreg_encabcum.jsp" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=123" target="_blank">
                       <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=123" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad"  /></a>
                       <a href="../virtualmax/rips/vista/MenuPrincipal.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/rips.png" class="tdimagen" /></a>
                       </td>
                       <td align="center" class="tdicono">
                       <a href="../bodega/inicio.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/bodega.1.png" class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=99" target="_blank">
                       <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=99" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../virtualmax/item/vista/negativos.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/bodega.1.2.11.png" class="tdimagen"/></a></td>
                     </tr>
                     <tr>
                       <td align="center" class="tdicono">
					   <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=52" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
					   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=52" target="_blank" title="Click para mesa de ayuda">
					   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../../virtualmax/item/vista/solicitud_nuevo.php"  target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/productos.nuevos.png"  class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=49" target="_blank">
                       <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=49" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../oc/clientes/actdatos.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/actualizacion.datos.1.png"  class="tdimagen"/></a></td>
                       <td align="center" class="tdicono">
                       <a href="../oc/cartera/tipo_cartera.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/cartera.1.1.1.png"  class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=51" target="_blank">
                       <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
					   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=51" target="_blank" title="Click para mesa de ayuda">
					   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../virtualmax/licitaciones/vista/index.php" target="_blank" >
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/licitaciones.check.list.png"  class="tdimagen"/></a></td>
                       <td align="center" class="tdicono">
					   <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=44" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
					   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=44" target="_blank" title="Click para mesa de ayuda">
					   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="vista/productos/clientesContrato.php"  target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/contratos.1.1..png"  class="tdimagen"/></a></td>
                     </tr>
                     <tr>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=100" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=100" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../encuesta_ext/cap_inico.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/capacitacion.clientes.png"  class="tdimagen" /></a></td>
                 	   <td align="center" class="tdicono">
                 	   <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=96" target="_blank">
                 	   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                 	   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=96" target="_blank" title="Click para mesa de ayuda">
                 	   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                 	   <a href="../bodega/recepcion/rnovedades.php" target="_blank">
                 	   <img src="../virtualmax/imagenes/iconosestandar/VENTAS/recibir.productos.png" class="tdimagen" longdesc="http://web.sivicos.gov.co:8080/consultas/consultas/consreg_encabcum.jsp" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=54" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=54" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="ventas/index.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/estadistico.ventas.png"  class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=101" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=101" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../bodega/ordencompra/fichasfin.php">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/fichgas.png" class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=55" target="_blank">
                       <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=55" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="bombas/index.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/consumo.bombas.png" class="tdimagen" /></a></td>
                     </tr>
                	                       
							   <?php
							   if($idRolTarea == 5 || $idRolTarea == 51)
							   {
								   echo 'Cotización'; 
							   }
						?>
                     
                       <td align="center" class="tdicono">
 					   <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=67" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
					   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=67" target="_blank" title="Click para mesa de ayuda">
					   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../bodega/resolucion.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/resolucion.clientes.png" class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
					   <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=56" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
					   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=56" target="_blank" title="Click para mesa de ayuda">
					   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../virtualmax/cotizacion/vista/index.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/cotizacion.png" class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
					   <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=5" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
					   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=5" target="_blank" title="Click para mesa de ayuda">
					   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../virtualmax/sobrestock/vista/index.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/sobrestock.png" class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
					   <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=96" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
					   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=96" target="_blank" title="Click para mesa de ayuda">
					   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="../bodega/recepcion/rnovedades.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/confirmar.producto.png" class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
					   <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=102" target="_blank">
					   <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
					   <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=102" target="_blank" title="Click para mesa de ayuda">
					   <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
                       <a href="/oc/auditorias/fa/digital_firma.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/auditar.fa.png" class="tdimagen" /></a></td>
                     </tr>
                     <tr>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=53" target="_blank">
                       <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=53" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
                       <a href="../virtualmax/compras/sugerido/vista/index.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/sugeridos.ordenes.png"  class="tdimagen"  /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=62" target="_blank">
                       <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad" /></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=62" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
                       <a href="../virtualmax/clientes/vista/listados.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/correoMasivo.png" class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/ventaonline/vista/estadisticas.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/pedidos.online.png"  class="tdimagen"  /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/proveedores/vista/menu_clientes.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/clientes.1.png"  class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=103" target="_blank">
                       <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
                       <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=103" target="_blank" title="Click para mesa de ayuda">
                       <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad"/></a>
                       <a href="../virtualmax/bodega/rutas/vista/reportefayrm.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/legalizacaciones.png"  class="tdimagen"  /></a></td>
                     </tr>
   
                     <tr>
                       <td align="center" class="tdicono">
                       <a href="../virtualmax/sismed/index.php" target="_blank">
                       <img src="../virtualmax/imagenes/iconosestandar/VENTAS/sismed.png" class="tdimagen" /></a></td>
                       <td align="center" class="tdicono">&nbsp;</td>
                       <td align="center" class="tdicono">&nbsp;</td>
                       <td align="center" class="tdicono">&nbsp;</td>
                       <td align="center" class="tdicono">&nbsp;</td>
                     </tr>
                   </table>
                 </div>                 

<?php if(($_SESSION['Rol']=="10") ||($_SESSION['Rol']=="15") ||($_SESSION['Id']==50)){ ?>
        <div class="TabbedPanelsContent">
          <table align="center" class="tablas" cellspacing="20" >
            <caption>GERENCIA O.C. LA ECONOMIA</caption>
            <tr>
		        <td width="144" align="center"><strong> Licitaciones Check List (<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=51" target="_blank">51</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=51" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" width="15" height="15"/></a></strong></td>
		        <td width="147" align="center"><strong>Contratos(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=44" target="_blank">44</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=44" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" width="15" height="15"/></a></strong></td>
		        <td width="132" align="center"><p><strong> Bodega</strong></p></td>
		        <td width="137" align="center"><strong> Analista Company(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=49" target="_blank">49</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=49" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" width="15" height="15"/></a></strong></td>
		        <td width="126" align="center"><strong>Estadistico Ventas (<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=54" target="_blank">54</a>)</strong><a href="../bodega/recepcion/INDEX.php" target="_blank"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" width="15" height="15"/></a></td>
            </tr>
            <tr>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=51" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=51" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad" /></a>
              <a href="../virtualmax/licitaciones/vista/index.php" target="_blank">
              <img src="imagenes/licitaciones.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=44" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=44" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" class="iconosSeguridad" /></a>
              <a href="vista/productos/clientesContrato.php"  target="_blank">
              <img src="imagenes/contratos3.jpg" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="../bodega/inicio.php" target="_blank">
              <img src="imagenes/Letter-B-icon.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=49" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=49" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../oc/clientes/actdatos.php" target="_blank">
              <img src="imagenes/analista.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=54" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=49" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
             
              <a href="ventas/index.php" target="_blank"><img src="imagenes/ventas.png" alt="" class="tdimagen"  /></a></td>
            </tr>
            <tr>
		        <td align="center"><strong> Gps</strong></td>
		        <td align="center"><strong> Mantenimiento de <br />
		Vehiculos(...)</strong></td>
		        <td align="center"><strong>Domicilios(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=89" target="_blank">89</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=89" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <td align="center"><strong> Polizas(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=63" target="_blank">63</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=63" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		              <td align="center"><strong>Auditoria</strong></td>
            </tr>
            <tr>
              <td align="center" class="tdicono">
              <a href="http://www.tusatelital.com" target="_blank">
              <img src="imagenes/RUTAS.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/MantenimientoVehiculos/vista/index.php" target="_blank">
              <img src="imagenes/mantenimiento.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=89" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=89" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="domicilio/vista/ver.php" target="_blank">
              <img src="imagenes/domicilio.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=63" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=63" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="polizas/vista/ver.php" target="_blank">
              <img src="imagenes/poliza.jpg" alt="" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="/oc/auditorias/" target="_blank">
              <img src="imagenes/auditoria.png" class="tdimagen" /></a></td>
            </tr>
            <tr>
		        <td align="center">Transportadoras(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=73" target="_blank">73</a>)<strong><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=73" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <td align="center"><strong> Viáticos(...)</strong></td>
		        <td align="center"><strong>Guias</strong></td>
		        <td align="center"><strong>Archivo virutal(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=87" target="_blank">87</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=87" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <td align="center"><strong>Quejas y Reclamos</strong></td>
            </tr>
            <tr>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=73" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=73" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../bodega/logistica/guias.php" target="_blank">
              <img src="imagenes/transportadoras.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="vista/viaticos/VistaOpciones.php" target="_blank">
              <img src="imagenes/viaticos.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="firma_cliente/seleccionar.php" target="_blank">
              <img src="imagenes/touch.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=87" target="_blank">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=87" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="vista/archivo_virtual/<"  target="_blank">
              <img src="imagenes/scan.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/bodega/qyr/vista/reporte.php" target="_blank">
              <img src="imagenes/quejas.png" class="tdimagen"  /></a></td>
            </tr>
            <tr>
		       <td align="center"><strong>Tarjeta EfiPago </strong></td>
		       <td align="center"><strong>Vacaciones (<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=27" target="_blank">27</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=27" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		       <td align="center"><strong>Orden de compra</strong></td>
		       <td align="center"><strong>Cartera(...)</strong></td>
		       <td align="center">Predeterminados<strong>(</strong></td>
            </tr>
            <tr>
              <td align="center" class="tdicono">
              <a href="vista/tarjetaEfipago/opcionesTarjeta.php" target="_blank">
              <img src="imagenes/creditCard.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=27" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=27" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="vista/vacaciones/funcionarios.php" target="_blank">
              <img src="imagenes/vacaciones.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="../bodega/ordencompra" target="_blank">
              <img src="imagenes/order-history-icon.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="../oc/cartera/tipo_cartera.php" target="_blank">
              <img src="imagenes/cartera.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=70" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=70" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../virtualmax/item/vista/predeterminados.php" target="_blank">
              <img src="../virtualmax/imagenes/botones/costos.png" class="tdimagen" /></a></td>
            </tr>
            <tr>
		        <td align="center"><strong>Presupuesto(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=46" target="_blank">46</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=46" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <td align="center">Faltantes Entidades</td>
		        <td align="center">Cotización(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=56" target="_blank">56</a>)<strong><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=56" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <td align="center">Proveedores(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=4" target="_blank">4</a>)<strong><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=4" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <th>Bancos (<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=45" target="_blank">45</a>)<strong><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=45" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></th>
            </tr>
            <tr>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=46" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=46" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../virtualmax/presupuesto/" target="_blank">
              <img src="../virtualmax/imagenes/iconos/presupuesto.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="../oc/clientes" target="_blank">
              <img src="imagenes/Clients.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=56" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=56" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../virtualmax/cotizacion/vista/index.php" target="_blank">
              <img src="../virtualmax/imagenes/botones/cotizacion.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=4" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=4" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../../virtualmax/proveedores/vista/index.php" target="_blank">
              <img src="imagenes/proveedor.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=45" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=45" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../../virtualmax/contabilidad/bancos/vista/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconos/bancos.png" class="tdimagen"  /></a></td>
            </tr>
            <tr>
		        <td align="center">Sobrestock  (<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=5" target="_blank">5</a>)<strong><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=5" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <td align="center">EPS</td>
		        <td align="center">Sugerido Compras(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=53" target="_blank">53</a>)<strong><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=53" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <td align="center">Bonificados(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=20" target="_blank">20</a>)<strong><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=20" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></td>
		        <th>Liquidacion (<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=30" target="_blank">30</a>)<strong><a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=30" target="_blank" title="Click para mesa de ayuda"><img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></strong></th>
            </tr>
            <tr>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=5" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=5" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../virtualmax/sobrestock/vista/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconos/stock.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/entidadesbig/vista/index.php">
              <img src="../virtualmax/imagenes/iconos/salud.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=53" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=53" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../virtualmax/compras/sugerido/vista/index.php" target="_blank">
              <img src="imagenes/FALTANTE1.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=20" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=20" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../virtualmax/ventas/bonificado/vista/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconos/bonificado.png" class="tdimagen" /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=30" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=30" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../virtualmax/recibocaja/vista/recibostotal.php" target="_blank">
              <img src="imagenes/pesos.jpg" class="tdimagen" /></a></td>
            </tr>
            <tr>
		        <td align="center">GPS Claro<br />
		                Usuario: AdminEco<br />
		                Clave: se002@</td>
		        <td align="center">Control Temperatura(<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=71" target="_blank">71</a>)<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=71" target="_blank" title="Click para mesa de ayuda">
		        <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a></td>
		              <td align="center">Sena</td>
		              <td align="center">Virtualmanager</td>
		              <td align="center">&nbsp;</td>
		    </tr>
		    <tr>
		        <td align="center" class="tdicono">
		        <a href="http://mscol.mobilesuitcase.com/general/sitio/login.aspx" target="_blank">
		        <img src="../virtualmax/imagenes/iconos/gps.png" class="tdimagen" /></a></td>
		        <td align="center" class="tdicono">
				<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=71" target="_blank">
				<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
				<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=71" target="_blank" title="Click para mesa de ayuda">
				<img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
		        <a href="../virtualmax/temperatura/vista/Menu_temperatura.php" target="_blank">
		        <img src="../virtualmax/imagenes/iconos/termometro.png" alt="" class="tdimagen"  /></a></td>
		        <td align="center" class="tdicono">
		        <a href="senafinal/vista/index.php" target="_blank">Sena</a></td>
		        <td align="center" class="tdicono">
		        	<a href="../virtualmax/virtualmanager/index.php" target="_blank">
	            		<img src="../virtualmax/imagenes/iconos/manager1.png" height="128" width="128" class="tdimagen"/>
	            	</a>
	            </td>
		        <td align="center" class="tdicono">&nbsp;</td>
            </tr>
          </table>
        </div>
<?php } ?>     
        <div class="TabbedPanelsContent">
            <table align="center" class="tablas" cellspacing="20" >
            <caption>CONTROL DOMICILIOS</caption>
            <tr>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=89" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=89" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="vista/archivo_virtual/"  target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/CONDUCTORES-DOMICILIARIOS/archivo.virtual.t.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=94" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=94" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="firma_cliente/seleccion.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/CONDUCTORES-DOMICILIARIOS/firma.guias.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/MantenimientoVehiculos/vista/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/CONDUCTORES-DOMICILIARIOS/mantenimiento.vehiculo.t.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=89" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=89" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a> 
              <a href="domicilio/vista/verdomi.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/CONDUCTORES-DOMICILIARIOS/domicilios.t.png" class="tdimagen"  /></a></td>
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=82" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=82" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="/oc/auditorias/fa/index.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/CONDUCTORES-DOMICILIARIOS/auditar.t.png" class="tdimagen" /></a></td>
            </tr>
            <tr>
              
              <td align="center" class="tdicono">
              <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=95" target="_blank">
              <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
              <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=95" target="_blank" title="Click para mesa de ayuda">
              <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
              <a href="../qyr/verrecoger.php" target="_blank">
              <img src="../virtualmax/imagenes/iconosestandar/CONDUCTORES-DOMICILIARIOS/recoger.medicamentos.png" class="tdimagen" /></a></td>
              <td class="tdicono"></td>
              <td align="center" class="tdicono">&nbsp;</td>
              <td align="center" class="tdicono">&nbsp;</td>
              <td align="center" class="tdicono">&nbsp;</td>
            </tr>
          </table>
        </div>

        <div class="TabbedPanelsContent">
          <table align="center" class="tablas" cellspacing="20" >
            <caption>TODOS</caption>

            <tr>
		      <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=87" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=87" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
		      <a href="vista/archivo_virtual/"  target="_blank">
		      <img src="../virtualmax/imagenes/iconosestandar/TODOS/archivo.virtual.c.d.png"  class="tdimagen" /></a></td>
		      <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=89" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=89" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
		      <a href="domicilio/vista/verdomi.php" target="_blank">
		      <img src="../virtualmax/imagenes/iconosestandar/TODOS/domicilios.c.d.png"  class="tdimagen" /></a></td>
		      <td align="center" class="tdicono">
		      <a href="http://www.tusatelital.com" target="_blank">
		      <img src="../virtualmax/imagenes/iconosestandar/TODOS/gps.c.d.png" alt=""  class="tdimagen" /></a></td>
		      <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=91" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=91" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
		      <a href="vista/viaticos/VistaOpciones.php" target="_blank">
		      <img src="../virtualmax/imagenes/iconosestandar/TODOS/viaticos.png"  class="tdimagen" /></a></td>
		      <td align="center" class="tdicono">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=86" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad">   </a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=86" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
		      <a href="/virtualmax/presupuesto/vista/indexusuario.php" target="_blank">
		      <img src="../virtualmax/imagenes/iconosestandar/TODOS/presupuesto.png" class="tdimagen" /></a></td>
            </tr>

            
            <tr>
	         	
	         	<td align="center" class="tdicono" >
	         	<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=85" target="_blank">
	         	<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
	         	<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=85" target="_blank" title="Click para mesa de ayuda">
	         	<img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
	          	<a href="../../virtualmax/inventario/vista/" target="_blank">
	          	<img src="../virtualmax/imagenes/iconosestandar/TODOS/invetario.png" alt="" class="tdimagen" /></a></td>
	            <td  align="center" class="tdicono">
	            <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=129" target="_blank">
	         	<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
	         	<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=129" target="_blank" title="Click para mesa de ayuda">
	         	<img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
	            <form name="form" action="//<?php echo $ipPublica;?>/qyr/admin.php" method="post" target="_blank">
				<input type="hidden" name="aplicacion" value="clientePqr"/>
				<input type="image" name="enviar" src="../virtualmax/imagenes/iconosestandar/TODOS/quejas.reclamos.cd.png" class="tdimagen" />
	            <input type="hidden" name="mensaje" value="No"/>
				</form>
	            </td>

	        	<td  align="center" class="tdicono">
				<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=32" target="_blank">
				<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
				<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=32" target="_blank" title="Click para mesa de ayuda">
				<img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
	        	<a href="../virtualmax/procesosyprocedimientos/index.php" target="_blank">
	        	<img src="../virtualmax/imagenes/iconosestandar/TODOS/procesos.procedimientos.png" class="tdimagen"  /></a></td>
	          	<td align="center" class="tdicono" >
				<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=90" target="_blank">
				<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
				<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=90" target="_blank" title="Click para mesa de ayuda">
				<img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
	          	<a href="../bodega/informaciontecnica/fichasfin.php" target="_blank">
	          	<img src="../virtualmax/imagenes/iconosestandar/TODOS/fichas.n.png" class="tdimagen"  /></a></td>
	          	<td align="center" class="tdicono">
	          	<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=119" target="_blank">
	          	<img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
	          	<a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=119" target="_blank" title="Click para mesa de ayuda">
	          	<img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
	          	<a href="../virtualmax/actualizar_info_tecnica/solicitud.php" target="_blank">
	          	<img src="../virtualmax/imagenes/iconosestandar/TODOS/invimas.n.png" class="tdimagen"  /></a></td>
 			</tr>

		    <tr>
			  <td class="tdicono" align="center">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=88" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=88" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
			  <a href="../virtualmax/solicitudpermiso/vista/menu_principal.php" target="_blank">
			  <img src="../virtualmax/imagenes/iconosestandar/TODOS/permisos.png" class="tdimagen" /></a>
			  <a href="../bodega/informaciontecnica/fichasfin.php" target="_blank"></a></td>
			  <td class="tdicono" align="center">
			  <a href="../virtualmax/item/vista/novedades.php" target="_blank">
			  <img src="../virtualmax/imagenes/iconosestandar/TODOS/novedaditem.png" class="tdimagen"  hidden="" /></a></td>
			  <td class="tdicono" align="center">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=84" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"></a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=84" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
			  <a href="../virtualmax/arqueoCajaMenor/vista/listaAnticipos.php" target="_blank">
			  <img src="../virtualmax/imagenes/iconosestandar/TODOS/solicitud.anticipo.png" class="tdimagen" /></a></td>
			  <td class="tdicono" align="center">
			  <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=73" target="_blank">
			  <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad">	</a>
			  <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=73" target="_blank" title="Click para mesa de ayuda">
			  <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
			  <a href="../bodega/logistica/guias.php" target="_blank">
			  <img src="../virtualmax/imagenes/iconosestandar/TODOS/trasnportadora.n.png" alt="" class="tdimagen" /></a></td>
			  <td class="tdicono" align="center">

			  <a href="../virtualmax/circular/vista/miscirculare.php" target="_blank">
			  <img src="../virtualmax/imagenes/iconosestandar/TODOS/mis.circulares.png" alt="" class="tdimagen" /></a>
			  
			  <a href="../virtualmax/solicitudpermiso/vista/menu_principal.php" target="_blank"></a></td>
	   	    </tr>
	   	    <tr>
	   	    	<td class="tdicono" align="center">
	   	    	<a href="../virtualmax/rbac/control/app_seguridad.php?id_app=124" target="_blank">
			    <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad">	</a>
			    <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=124" target="_blank" title="Click para mesa de ayuda">
			    <img src="../virtualmax/imagenes/iconos/campana.png" alt="" class="iconosSeguridad" /></a>
			    <a href="../virtualmax/circular2.0/vista/" target="_blank">
			    <img src="../virtualmax/imagenes/iconosestandar/RR-HH/circulares.internas.png" alt="" class="tdimagen" /></a>	
	   	    	</td>
	   	    	<td class="tdicono" align="center"></td>
	   	    	<td class="tdicono" align="center"></td>
	   	    	<td class="tdicono" align="center"></td>
	   	    	<td class="tdicono" align="center"></td>
	   	    </tr>
		    <!--<tr>
		      <td align="center" class="tdicono"><a href="../bodega/logistica/guias.php" target="_blank" hidden="">guias</a></td>
		      <td class="tdicono">&nbsp;</td>
		      <td class="tdicono">&nbsp;</td>
		      <td class="tdicono">&nbsp;</td>
		      <td align="center" class="tdicono">&nbsp;</td>
		    </tr>-->       
    	</table>
        </div>
      <div class="TabbedPanelsContent" hidden="">
<table  align="center" class="tablas" cellspacing="20">
	<caption>DEPARTAMENTO DE DISEÑO</caption>
    <tr>
      <td align="center" class="tdicono">
       <a href="../virtualmax/rbac/control/app_seguridad.php?id_app=" target="_blank">
      <img src="../virtualmax/imagenes/iconos/keys.png"  class="iconosSeguridad" title="Seguridad"/></a>
      <a href="../virtualmax/mesaAyuda/vista/Solicitud_aplicacion.php?aplicacion=" target="_blank">
      <img src="../virtualmax/imagenes/iconos/campana.png"  class="iconosSeguridad" title="Mesa de ayuda App"/></a>
      <a href="../bodega/surtidor/vista/auditaFoto.php"  target="_blank">
      <img src="../virtualmax/imagenes/iconosestandar/cartera/contratos.png" class="tdimagen" /></a></td>
      <td align="center" class="tdicono"></td>
            <td align="center" class="tdicono"> </td>
     <td class="tdicono"></td>
     <td class="tdicono"></td>  
    </tr>
  </table>       
      </div>
        
        <div class="TabbedPanelsContent"><strong><font color="#FF0000" size="+3"></font></strong></div>
</div>
</div>
<script>
	function abrirEnlaces(url1,url2){
	  window.open('../bodega/inicio.php');
	  
	}
</script>

	
<p align="center">
						
			
			
<?php
		

//***************************************menu de DOMICILIARIOS****************************
if($_SESSION['Rol']=="17")
{	
include('domicilio/control/Conexion.php');
include('domicilio/dao/domiciliario.php');
include("../qyr/conexion.php");	
$con=conectar();
$dv=mysql_query('select count(*) from qyr where recoger=1 and finalizarr=0')or die(mysql_error());

$res=mysql_fetch_array($dv);

if($res[0]>0){
//echo '<script>alert("Medicamentos pendientes por recoger ")/script>';
	
	}



$dao=new domiciliario();
$resultado=$dao->sonido();
$res=mysql_fetch_array($resultado);
if($res[0]>0){?>
	 <script language="javascript">sonido.play();</script> 
	
<?php 	}?>
<?php			
}					
?>
        <br />
    </p>
<!--<script type="text/javascript" src="engine1/wowslider.js"></script>-->
	<script type="text/javascript" src="engine1/script.js"></script> 
<table width="949" height="123" border="0" style="border:2px dashed #CCC; ">
      <tr>
          <td width="94" align="center"><strong>Google</strong></td>
          <td width="97" align="center"><strong>Yahoo</strong></td>
          <td width="108" align="center"><strong>Dian</strong></td>
          <td width="107" align="center"><strong>Supersolidaria</strong></td>
          <td width="121" align="center"><strong>Invima</strong></td>
          <td width="118" align="center"><strong>Consulta Invima</strong></td>
          <td width="125" align="center"><strong>Sigcoop</strong></td>
          <td width="141" align="center"><strong>Mi Farmacia <br />
          Group</strong></td>
        </tr>
        <tr>
          <td height="92" align="center"><a href="http://www.google.com.co" target="_blank"><img src="imagenes/Google-Chrome-icon.png" width="58" height="60"></a></td>
          <td align="center"><a href="http://www.yahoo.es" target="_blank"><img src="imagenes/yahoo-icon.png" width="56" height="59"></a></td>
          <td align="center"><a href="http://www.dian.gov.co" target="_blank"><img src="imagenes/índice.jpg" width="104" height="46" /></a></td>
          <td align="center"><a href="http://www.supersolidaria.gov.co/" target="_blank"><img src="imagenes/supersolidaria.jpg" width="79" height="56"></a></td>
          <td align="center"><a href="http://www.invima.gov.co" target="_blank"><img src="imagenes/invima.jpg" width="113" height="40"></a></td>
          <td align="center"><a href="http://farmacovigilancia.invima.gov.co:8082/Consultas/consultas/consreg_encabcum.jsp" target="_blank"><img src="imagenes/consulta_invima.png" width="85" height="23" /></a></td>
          <td align="center"><a href="http://confecoop.coop/index.php/productos/sigcoop" target="_blank"><img src="imagenes/confecoop.jpg" width="125" height="60" /></a></td>
          <td align="center"><a href="http://www.mifarmacia.com.co" target="_blank"><img src="../virtualmax/imagenes/iconos/logofarmaciagroup.png" width="110" height="33" /></a></td>
        </tr>
</table>
      <br>
      <table width="949" height="123" border="0" style="border: 2px dashed #CCC; font-weight: bold;">
        <tr>
          <td width="143" align="center"><strong>Copidrogas</strong></td>
          <td width="127" align="center"><strong>Bionexo</strong></td>
          <td align="center"><strong>Supersalud</strong></td>
          <td width="125" align="center"><strong>BBVA</strong></td>
          <td width="121" align="center"><strong>Sudameris</strong></td>
          <td width="144" align="center"><strong>Agrario</strong></td>
          <td width="136" align="center"><strong>Occidente</strong></td>
        </tr>
        <tr>
          <td height="92" align="center"><a href="http://www.copidrogas.com.co" target="_blank"><img src="imagenes/copi.jpg" width="98" height="33" /></a></td>
          <td align="center"><a href="https://www.bionexo.com.co" target="_blank"><img src="imagenes/logo-bionexo_n2.png" width="74" height="58" /></a></td>
          <td align="center"><a href="http://www.supersalud.gov.co/supersalud/" target="_blank"><img src="imagenes/supersalud.jpg" width="130" height="40" /></a></td>
          <td align="center"><a href="https://www.bbva.com.co/" target="_blank"><img src="imagenes/bbva.jpg" width="100" height="32"></a></td>
          <td align="center"><a href="http://www.gnbsudameris.com.co/" target="_blank"><img src="imagenes/sudameris.jpg" width="84" height="66" /></a></td>
          <td align="center"><a href="http://www.bancoagrario.gov.co/Paginas/default.aspx" target="_blank"><img src="imagenes/agrario.jpg" width="84" height="66"></a></td>
          <td align="center"><a href="https://occired1.bancodeoccidente.com.co/BancaCorporativa/BancaCorporativaOccired.asp" target="_blank"><img src="imagenes/occidente.jpg" width="73" height="56"></a></td>
        </tr>
        
      </table> 
<!--funcion de moviumiento de reloj en tiempo real esto conlleva a un formulario y la etiqueta body llamala funcion mueveReloj-->
<script language="JavaScript"> 
function mueveReloj(){ 
   	momentoActual = new Date(); 
   	hora = momentoActual.getHours(); 
   	minuto = momentoActual.getMinutes(); 
   	segundo = momentoActual.getSeconds(); 
   	var sufijo = ' am';
   	if(hora > 12) {
		  hora = hora - 12;
		  sufijo = ' pm';
	}
	if(hora < 10) { hora = '0' + hora; }
	if(minuto < 10) { minuto = '0' + minuto; }
	if(segundo < 10) { segundo = '0' + segundo; }

	if((hora == 10 && minuto >= 0)&&(hora == 10 && minuto <= 05) || (hora == 04 && minuto >= 0)&&(hora == 04 && minuto <= 5)){
		//alert('<h1>Pausa activa</h1>');
		//$notificaciones ++;
		$pausasActivas = 1;
		location.href="../virtualmax/notificaciones/vistas/index.php?pausas=1"+"&dc="+<?php echo $documento?>;
		//location.href="../virtualmax/notificaciones/vistas/index.php?pausas="+pausasActivas;
	}
   
   	horaImprimible = hora + " : " + minuto + " : " + segundo+sufijo;
  	
   	document.form_reloj.reloj.value = horaImprimible ;

   	setTimeout("mueveReloj()",1000) ;
} 
</script> 
      <form name="form_reloj"> 
		 <div class="divcajatexto"><input class="caja" type="text" name="reloj" 
                value="<?php $horafecha= date ('h:i d-m-Y');?>" onfocus="window.document.form_reloj.fechahora.blur()" style="background:#09F;  color:#FFF;">
         </div> 
         
	  </form> 
   	 
      </p>
      <p align="center">Diseño: Departamento Tecnológico
        <br>
        <em><strong>©</strong></em> Copyright: O.C La Economía - Mediqboy
    </p>
  <script type="text/javascript">
var TabbedPanels1 = new Spry.Widget.TabbedPanels("TabbedPanels1", {defaultTab:14});
  </script>
</body>
</html>