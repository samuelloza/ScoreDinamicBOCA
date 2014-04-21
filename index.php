<!DOCTYPE html>
<html>
<head>
	<script type="text/javascript">
	//ori sam
	//new nuevo
	var indexnuevo="";
	
	window.onload = function() {
		borrarepetido();
		var indexori=0;
		var id="";
		for(var sam=0;sam<19;sam++){
			id="";
			indexnuevo="";
			indexori=0;
			id=splitUser(sam);
			var ff=0;
			while(indexori < 18){
				sss=document.getElementById("ori").rows[indexori].innerHTML;
				if(buscanuevo(sss,id)){
					break;
				}
				indexori++;
			}

			if(indexori >= indexnuevo){
			//solo subidas de score respecto al nuevo
			animate(indexori,indexnuevo);
		}else{
				//si mantiene lugar 
				var a=deletenumer(sam,0,"0");
				document.getElementById("ori").rows[sam].innerHTML=a;
			}
			
			deletenumer(sam,1,sam+1); //bug
		}
		
	}

	function animate(indexori,indexnuevo){
		if(indexori >= indexnuevo){
			//indexnuevo--;
			while(indexori >= indexnuevo){
				if(indexori!=indexnuevo){
					var a=deletenumer(indexnuevo-1,0,"0");
					var b=document.getElementById("ori").rows[indexori-1].innerHTML;
					document.getElementById("ori").rows[indexori-1].innerHTML=a;
					document.getElementById("ori").rows[indexori].innerHTML=b;
				}else{
					return;
				}
				alert("");
				indexori--;
				sleep(550);
			//	animate(indexori,indexnuevo); recursivo 
		}
	}
}

function sleep(milliseconds) {
	var d = new Date();
	var begin = d.getTime();
/*
	Encontrar alguna forma de q no se muestre el alert
	para q se vea la animacion, si no es muy rapido y no
	se ve nada ;(
		*/
		while (Math.abs((d.getTime()-begin ))<milliseconds){
			//alert("-");
			var d2=new Date();
			begin = d2.getTime();
		} 
	}
	//function alert() {}
/*
*
input :
inde = index de row a cambiar o mantener
ori = cadena donde esta el tr
			op  = si elimina o actualiza; 0 mantiene 1 cambia
			*
			*/
			function deletenumer(inde,op,numer){
				var ori=document.getElementById("nuevo").rows[inde].innerHTML;
				var ans="";
				var id="";
				var flag=true;
				ans="<";
				for(var j=1;j<ori.length;j++){
					if(flag&&ori[j-1]=='>' && (ori[j]>='0' && ori[j]<='9')){
						while(ori[j]!='<'){
							if(op==1&&flag){
								ans+=numer+"";
							}
							j++;
							flag=false;
						}
					}
					ans+=ori[j];
				}
				if(op){
					document.getElementById("ori").rows[inde+1].innerHTML=ans;
				}
				return ans;
			}
			function buscanuevo(cade,busca){
				var conta=0;
				var id="";
				for(var j=0;j<cade.length-2;j++){
					if(conta < 2 &&(cade[j]=='<'&&cade[j+1]=='t' && cade[j+2]=='d')){
						conta++;
					}else{
						if(conta==2){
							var k=j;
							while(cade[k]!='>'){
								k++;
							}
							k++;
							for( ;k<cade.length-4;k++){
								if(cade[k]=='<' && cade[k+1]=='/' && cade[k+2]=='t'&&cade[k+3]=='d'&&cade[k+4]=='>'){
									break;
								}else{
									id=id+cade[k];
								}
							}
							break;
						}
					}

				}
				if(id==busca){
					return true;
				}else return false;

			}

			function borrarepetido(){
				for(var i=0;i< 19;i++){	
					document.getElementById("nuevo").deleteRow(i+1);
					document.getElementById("ori").deleteRow(i+1);
				}
			}

			function splitUser(index){
				var conta=0,id="";
				var sss=document.getElementById("nuevo").rows[index].innerHTML;
				for(var j=0;j<sss.length-2;j++){
					if(conta < 2 &&(sss[j]=='<'&&sss[j+1]=='t' && sss[j+2]=='d')){
						conta++;
						if(conta==1){
							j+=4;
							while(sss[j]!='<' && sss[j+1]!='/'){
								indexnuevo+=sss[j];
								j++;
							}
						}
					}else{
						if(conta==2){
							var k=j;
							while(sss[k]!='>'){
								k++;
							}
							k++;
							for( ;k<sss.length-4;k++){
								if(sss[k]=='<' && sss[k+1]=='/' && sss[k+2]=='t'&&sss[k+3]=='d'&&sss[k+4]=='>'){
									break;
								}else{
									id=id+sss[k];
								}
							}	
							return id;
						}
					}
				}
				return id;
			}
		</script>

	</head>
	<body>
		<?php
		include'../scoreori.html';
		echo "<div style='display:none;'>";
		include'../scorenew.html';
		echo"</div>";
//echo "<br><br>".$new;
		?>
	</body>
	</html>