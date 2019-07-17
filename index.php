<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no" />
<title>Ringover Speedtest</title>
<style type="text/css">
	html,body{
		border:none; padding:0; margin:0;
		background:#FFFFFF;
		color:#202020;
	}
	body{
		text-align:center;
		font-family:"Roboto",sans-serif;
	}
	h1{
		color:#404040;
		margin-top:50px;
	}
	#test{
		margin-top:2em;
		margin-bottom:12em;
		margin-top:120px;;	
	}
	div.testArea{
		display:inline-block;
		width:16em;
		height:12.5em;
		position:relative;
		box-sizing:border-box;
	}
	div.testName{
		position:absolute;
		top:0.1em; left:0;
		width:100%;
		font-size:1.4em;
		z-index:9;
	}
	div.meterText{
		position:absolute;
		bottom:1.55em; left:0;
		width:100%;
		font-size:2.5em;
		z-index:9;
	}
	div.meterText:empty:before{
		content:"0.00";
	}
	div.unit{
		position:absolute;
		bottom:2em; left:0;
		width:100%;
		z-index:9;
	}
	div.testArea canvas{
		position:absolute;
		top:0; left:0; width:100%; height:100%;
		z-index:1;
	}
	div.testGroup{
		display:inline-block;
	}
	#logo{
		margin-top:80px;
	}
	#ipArea{
			margin-top:100px;
	}
	@media all and (max-width:65em){
		body{
			font-size:1.5vw;
		}
	}
	@media all and (max-width:40em){
		div.meterText{
			font-size:2em;
		}
		div.testName{
			font-size:1em;
		}
		body{
			font-size:0.8em;
		}
		div.testGroup{
			display:block;
			margin: 0 auto;
		}
		#test{
			margin-top:20px;	
		}
	    div.testArea{
			width: 13em;
    		height: 11em;
			margin-top:20px;
			margin-bottom:20px;
		}
		#ipArea{
			margin-top:20px;
		}
		#logo{
			margin-top:20px;
			margin-bottom:20px;
		}
	}

</style>
<script type="text/javascript">
function I(id){return document.getElementById(id);}
var meterBk="#E0E0E0";
var dlColor="#3498db",
	ulColor="#5fd1d3",
	pingColor="#9b59b6",
	jitColor="#f1c40f";
var progColor="#EEEEEE";

//CODE FOR GAUGES
function drawMeter(c,amount,bk,fg,progress,prog){
	var ctx=c.getContext("2d");
	var dp=window.devicePixelRatio||1;
	var cw=c.clientWidth*dp, ch=c.clientHeight*dp;
	var sizScale=ch*0.0055;
	if(c.width==cw&&c.height==ch){
		ctx.clearRect(0,0,cw,ch);
	}else{
		c.width=cw;
		c.height=ch;
	}
	ctx.beginPath();
	ctx.strokeStyle=bk;
	ctx.lineWidth=16*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,Math.PI*0.1);
	ctx.stroke();
	ctx.beginPath();
	ctx.strokeStyle=fg;
	ctx.lineWidth=16*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,amount*Math.PI*1.2-Math.PI*1.1);
	ctx.stroke();
	if(typeof progress !== "undefined"){
		ctx.fillStyle=prog;
		ctx.fillRect(c.width*0.3,c.height-16*sizScale,c.width*0.4*progress,4*sizScale);
	}
}
function mbpsToAmount(s){
	return 1-(1/(Math.pow(1.3,Math.sqrt(s))));
}
function msToAmount(s){
	return 1-(1/(Math.pow(1.08,Math.sqrt(s))));
}

//SPEEDTEST AND UI CODE
var w=null; //speedtest worker
var data=null; //data from worker
function startStop(){
	if(w!=null){
		//speedtest is running, abort
		w.postMessage('abort');
		w=null;
		data=null;
		initUI();
	}else{
		//test is not running, begin
		w=new Worker('speedtest_worker.min.js');
		w.postMessage('start'); //Add optional parameters as a JSON object to this command
		w.onmessage=function(e){
			data=JSON.parse(e.data);
			var status=data.testState;
			if(status>=4){
				//test completed
				w=null;
				updateUI(true);
			}
		};
	}
}
//this function reads the data sent back by the worker and updates the UI
function updateUI(forced){
	if(!forced&&(!data||!w)) return;
	var status=data.testState;
	I("ip").textContent=data.clientIp;
	I("dlText").textContent=(status==1&&data.dlStatus==0)?"...":data.dlStatus;
	drawMeter(I("dlMeter"),mbpsToAmount(Number(data.dlStatus*(status==1?oscillate():1))),meterBk,dlColor,Number(data.dlProgress),progColor);
	I("ulText").textContent=(status==3&&data.ulStatus==0)?"...":data.ulStatus;
	drawMeter(I("ulMeter"),mbpsToAmount(Number(data.ulStatus*(status==3?oscillate():1))),meterBk,ulColor,Number(data.ulProgress),progColor);
	I("pingText").textContent=data.pingStatus;
	drawMeter(I("pingMeter"),msToAmount(Number(data.pingStatus*(status==2?oscillate():1))),meterBk,pingColor,Number(data.pingProgress),progColor);
	I("jitText").textContent=data.jitterStatus;
	drawMeter(I("jitMeter"),msToAmount(Number(data.jitterStatus*(status==2?oscillate():1))),meterBk,jitColor,Number(data.pingProgress),progColor);
}
function oscillate(){
	return 1+0.02*Math.sin(Date.now()/100);
}
//poll the status from the worker (this will call updateUI)
setInterval(function(){
	if(w) w.postMessage('status');
},200);
//update the UI every frame
window.requestAnimationFrame=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.msRequestAnimationFrame||(function(callback,element){setTimeout(callback,1000/60);});
function frame(){
	requestAnimationFrame(frame);
	updateUI();
}
frame(); //start frame loop
//function to (re)initialize UI
function initUI(){
	drawMeter(I("dlMeter"),0,meterBk,dlColor,0);
	drawMeter(I("ulMeter"),0,meterBk,ulColor,0);
	drawMeter(I("pingMeter"),0,meterBk,pingColor,0);
	drawMeter(I("jitMeter"),0,meterBk,jitColor,0);
	I("dlText").textContent="";
	I("ulText").textContent="";
	I("pingText").textContent="";
	I("jitText").textContent="";
	I("ip").textContent="";
	startStop();
}

</script>
</head>
<body>
<img id="logo" src="https://myringover.com/img/logo-ringover-full.svg" /><br />
<div id="test">
	<div class="testGroup">
		<div class="testArea">
			<div class="testName">Download</div>
			<canvas id="dlMeter" class="meter"></canvas>
			<div id="dlText" class="meterText"></div>
			<div class="unit">Mbps</div>
		</div>
		<div class="testArea">
			<div class="testName">Upload</div>
			<canvas id="ulMeter" class="meter"></canvas>
			<div id="ulText" class="meterText"></div>
			<div class="unit">Mbps</div>
		</div>
	</div>
	<div class="testGroup">
		<div class="testArea">
			<div class="testName">Ping</div>
			<canvas id="pingMeter" class="meter"></canvas>
			<div id="pingText" class="meterText"></div>
			<div class="unit">ms</div>
		</div>
		<div class="testArea">
			<div class="testName">Jitter</div>
			<canvas id="jitMeter" class="meter"></canvas>
			<div id="jitText" class="meterText"></div>
			<div class="unit">ms</div>
		</div>
	</div>
	<div id="ipArea">
		IP Address: <span id="ip"></span>
	</div>
<!--div id="startStopBtn" onclick="startStop()"></div-->
</div>
<script type="text/javascript">setTimeout(initUI,100);</script>
</body>
</html>