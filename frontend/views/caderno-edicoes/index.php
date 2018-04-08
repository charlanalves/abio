<?php
/* @var $this yii\web\View */
use frontend\assets\DhtmlxAsset;
DhtmlxAsset::register($this);
$this->title = '';
?>

<input type="button" class="btn btn-warning" value="Upload do Jornal" onclick="W.enviaCanerno()" />
<style>
    .content-header>h1 {
    margin: 0;
    font-size: 18px;
    background-color: #c6d3db;
    border-radius: 3px;
    padding: 10px;
    padding-left: 17px;
    height: 46px;
    line-height: 25px;
    background: rgba(242,246,248,1);
    background: -moz-linear-gradient(left, rgba(242,246,248,1) 0%, rgba(181,198,208,1) 0%, rgba(216,225,231,1) 56%, rgba(224,239,249,1) 100%);
    background: -webkit-gradient(left top, right top, color-stop(0%, rgba(242,246,248,1)), color-stop(0%, rgba(181,198,208,1)), color-stop(56%, rgba(216,225,231,1)), color-stop(100%, rgba(224,239,249,1)));
    background: -webkit-linear-gradient(left, rgba(242,246,248,1) 0%, rgba(181,198,208,1) 0%, rgba(216,225,231,1) 56%, rgba(224,239,249,1) 100%);
    background: -o-linear-gradient(left, rgba(242,246,248,1) 0%, rgba(181,198,208,1) 0%, rgba(216,225,231,1) 56%, rgba(224,239,249,1) 100%);
    background: -ms-linear-gradient(left, rgba(242,246,248,1) 0%, rgba(181,198,208,1) 0%, rgba(216,225,231,1) 56%, rgba(224,239,249,1) 100%);
    background: linear-gradient(to right, rgba(242,246,248,1) 0%, rgba(181,198,208,1) 0%, rgba(216,225,231,1) 56%, rgba(224,239,249,1) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f2f6f8', endColorstr='#e0eff9', GradientType=1 );
}


.btn-warning {
    background-color: #f39c12;
    border-color: #e08e0b;
    position: relative;
    top: -55px;
    float: right;
    left: -18px;
}
</style>
<script>
    W = function(){};
    W.uploadCaderno = {};
    var gridJournal;
    
    W.uploadCaderno.init = function() {
        WindowsDhtmlx = new dhtmlXWindows();
        W.uploadCaderno.window = WindowsDhtmlx.createWindow("uploadCaderno", 0,0, 750, 450);
        W.uploadCaderno.window.button('minmax1').hide();
        W.uploadCaderno.window.button('park').hide();
        W.uploadCaderno.window.denyResize();
        W.uploadCaderno.window.center();
        W.uploadCaderno.close();
        W.uploadCaderno.window.attachEvent("onClose", function(win){
            W.uploadCaderno.close();
             gridJournal.recarregaGrid();
        });
    };
    W.uploadCaderno.close = function() {
        W.uploadCaderno.window.hide();
        W.uploadCaderno.window.setModal(false);
    };
    W.uploadCaderno.show = function() {
        W.uploadCaderno.window.show();
        W.uploadCaderno.window.setModal(true);
    };
    
    W.processaPDF = function(data) {
        console.log(data);
        
        file = data[0].fileName;
        dt = data[0].dataJournal;
        tp = data[0].value;
        
        url = 'index.php?r=caderno-edicoes/processa-caderno';
        params = 'file='+file+'&dt='+dt+'&tp='+tp;
        dhtmlxAjax.postSync(url, params, function (a){
            if(a.xmlDoc.status === 200){
		url = 'index.php?r=importar-edicao/processa-pdf';
		dhtmlxAjax.postSync(url, '', function (a){
		    if(a.xmlDoc.status === 200){
			
		    }
		});
		    
            }
        });
      
    };
    
    W.enviaCanerno = function() {
        W.uploadCaderno.window.setText('Enviar Cadernos');
        W.uploadCaderno.window.attachURL('../../vendor/FileUpload/index.php');
        W.uploadCaderno.show();
    }
    
    document.addEventListener("DOMContentLoaded", function(event) {
        
        window.testeLayout = new dhtmlXLayoutObject("layoutObj", "1C");
	window.testeLayout.cells("a").setText('Jornais cadastrados');
			
	gridJournal = window.testeLayout.cells("a").attachGrid();
        gridJournal.setHeader("Código do Jornal,Data,Status,Excluir");
        gridJournal.attachHeader(",#text_filter,#select_filter,");
        gridJournal.setInitWidths("100,*,*,100");        
        gridJournal.setColAlign("center,center,center,center");
        gridJournal.setColTypes("ro,ro,ro,img");
        gridJournal.init();    
        gridJournal.recarregaGrid = function(){
            dhtmlxAjax.post('index.php?r=caderno-edicoes/grid-journal', {}, function (a){      
                   gridJournal.loadXMLString(a.xmlDoc.response.replace('<head/>',''));
            });
        }
         gridJournal.recarregaGrid();
        W.uploadCaderno.init();
    });
    
    function deleteJournal(id_journal){
        dhtmlx.confirm ({
            text:"Deseja excluir o jornal?", 
            callback: function(r){
                if(r){
                    url = 'index.php?r=caderno-edicoes/delete-journal';
                    params = 'id_journal='+id_journal;
                    dhtmlxAjax.post(url, params, function (a){        
                        dhtmlx.alert({text:"Jornal excluido com sucesso!",callback: function(){
                            gridJournal.recarregaGrid();
                        }});
                    });
                }
            }
        });
    }
    
</script>

<div id="layoutObj" style="position: relative; top: 0px; left: 0px; right: 0px; width: 100%!important; height: 350px;"></div>
