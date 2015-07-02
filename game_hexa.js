// JavaScript Document
/*
공대여자, mins01.com
제작 : 2013-04-14
수정 : 
임의사용 금지!
*/
var Game_hexa = function(div_layout,board,bkD,nextBkD){
	this.init(div_layout,board,bkD,nextBkD)
};

Game_hexa.prototype = {
	 "board":null
	,"div_layout":null
	,"bkD":null
	,"nextBkD":null
	,"score":0
	,"scoreTds":0
	,"scoreBks":[]
	,"level":0
	,"tdsLength":0
	,"blockWidth":40
	,"trsTds":[]
	,"tds":[]
	,"tdsInBkD":[]
	,"tdsInNextBkD":[]
	,"colorRange":6 
	,"colorTbl":0
	,"useConsoleLog":true
	,"ableMove":false
	,"ableDrop":false
	,"termTimmer":1000
	,"initTermTimmer":500
	,"timmer":null
	,"steps":[]
	,"onchangescore":null
	,"onstart":null
	,"ongameover":null
	,"playing":false
	,"init":function(div_layout,board,bkD,nextBkD){
		this.div_layout = div_layout;
		this.board = board;
		this.bkD = bkD;
		this.nextBkD = nextBkD;
	}
	,"consoleLog":function(str1){
		if(this.useConsoleLog && window.console && console.log){
			console.log(str1);
		}
	}
	,"start":function(){
		this.stop();
		this.trsTds = [];
		var trs = _M.$tag('tr',this.board);
		for(var i=0,m=trs.length;i<m;i++){
			this.trsTds.push(_M.$tag('td',trs[i]));
		}
		this.tdsLength = this.trsTds[0].length;
		this.tdsInBkD = _M.$tag('td',this.bkD);
		this.tdsInNextBkD = _M.$tag('td',this.nextbkD);
		
		this.tds = _M.$tag('td',this.board);
		this.colorTable(0);
		this.clearTds(); //TD들 초기화
		this.steps = [];
		this.playing = true;
		this.ableMove = true;
		this.ableDrop = true;
		this.scoreTds = 0;
		this.scoreBks = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]; //블럭별 처리갯수 초기화
		this.score = 0;
		this.level = 0;
		this.numberBlock = 3;


		this.checkLevelup()
		//this.initBkD();
		this.initBkD();
		this.resume()
		if(this.onstart!=null) this.onstart();
		this.stepMoveDownBkD();
	}
	,"resume":function(){
		this.hideBkD(1);
		this.playing = true;
		this.addStep(this.nodo,1000,0,'nodo')
		this.onchangescore();

		this.startStep();
	}
	,"stop":function(){
		this.playing = false;	
		this.hideBkD(0);
		this.stopStep();
		this.consoleLog("stop()");
	}
	,"startStep":function(){
		this.intervalStep();
		this.consoleLog("startStep()");
	}
	,"stopStep":function(){
		if(this.timmer!=null){
			clearTimeout(this.timmer);
		}
		this.consoleLog("stopStep()");
	}
	,"nodo":function(){
	}
	,"colorTable":function(n,isAdd){
		var t = this.div_layout.className;
		if(!isAdd){
			t = t.replace(/(^|\s)table_color_\w($|\s)/g,'');
		}
		t+=" table_color_"+n;
		if(!isNaN(n)){
			this.colorTbl = n;
		}
		this.div_layout.className = t;
	}
	,"intervalStep":function(){
		if(!this.playing) { this.stopStep(); return false; }
		if(this.steps.length<=0){ return false;}
//		this.consoleLog("steps.length :"+this.steps.length);
		var arr = this.steps.shift();
//		this.consoleLog("steps.length :"+this.steps.length);
		arr[0]();
		this.timmer = setTimeout(
			function(thisC){
				return function(){
					thisC.intervalStep();
				}
			}(this)
			,arr[1]);
	}
	,"resetStep":function(){
		this.stopStep();
		this.startStep();
	}
	,"addStep":function(fn,tm,isUnshift,label){
		if(!this.playing) return false;
		if(!isUnshift){
			this.steps.push([fn,tm,label]); 
		}else{
			this.steps.unshift([fn,tm,label]);
		}
	}
	,"posBkD":function(){
		var bkD = this.bkD;
		return [parseInt(bkD.style.left),parseInt(bkD.style.top)];
	}
	,"moveByBkD":function(left,top){
		var bkD = this.bkD;
		if(left!==false) bkD.style.left = (parseInt(bkD.style.left)+left)+"px";
		if(top!==false) bkD.style.top = (parseInt(bkD.style.top)+top)+"px";
	}
	,"moveToBkD":function(left,top){
		var bkD = this.bkD;
		if(left!==false) bkD.style.left = (left)+"px";
		if(top!==false) bkD.style.top = (top)+"px";
	}
	//수평이동
	,"moveHBkD":function(isRight){
		if(!this.playing){return false;}
		if(!this.ableMove){return false;}
		var ts = this.posBkD();
		if(ts[0]%this.blockWidth !==0){return false;}
		var leftB = (ts[0] / this.blockWidth);
		var topB = Math.ceil(ts[1] / this.blockWidth)+2; 
		if(isRight){
			if(leftB >= (this.tdsLength-1)
			|| (topB>=0 && this.trsTds[topB][leftB+1].className !="")
			|| (topB>=1 && this.trsTds[topB-1][leftB+1].className !="")
			|| (topB>=2 && this.trsTds[topB-2][leftB+1].className !="")
			){
				this.consoleLog("오른쪽으로 이동 불가, 최대위치")
				return false;
			}
			this.moveByBkD(this.blockWidth,0);
		}else{
			if(leftB <=0
			|| (topB>=0 && this.trsTds[topB][leftB-1].className !="")
			|| (topB>=1 && this.trsTds[topB-1][leftB-1].className !="")
			|| (topB>=2 && this.trsTds[topB-2][leftB-1].className !="")
			){
				this.consoleLog("왼쪽으로 이동 불가, 최저위치")
				return false;
			}
			this.moveByBkD(-1*this.blockWidth,0);
		}
	}
	//바로 내리기이동
	,"dropBkD":function(){
		if(!this.playing){return false;}
		if(!this.ableDrop){return false;}
		this.checkLeaveBkD();
		var ts = this.posBkD();
		if(ts[0]%this.blockWidth !==0){return false;}
		var leftB = (ts[0] / this.blockWidth);
		//var topB = Math.ceil(ts[1] / this.blockWidth)-2+3; 
		var trsTds = this.trsTds;
		var i=0;
		for(i=0,m=trsTds.length; i<m ;i++){
			if(trsTds[i][leftB].className!=""){
				break;
			}
		}
		//---
		//var t = Math.max((i-2),-3);
		//var t = i-2;
		i =  Math.min(i,trsTds.length);
		i =  Math.max(i,0);
		this.consoleLog("drop : "+i);
		var t = i-3;
		this.moveToBkD(false,t*this.blockWidth);
		this.checkLeaveBkD();
	}
	//하강이동
	,"moveDownBkD":function(){
		if(!this.checkLeaveBkD()){
			this.moveByBkD(0,10);
		}
	}
	,"stepMoveDownBkD":function(){
		if(this.playing){
			this.checkLeaveBkD()
			this.moveByBkD(0,10);
			this.addStep(
				(function(thisC){ 
					return function(){
						thisC.stepMoveDownBkD();
					}
				})(this)
				,this.termTimmer,0,'stepMoveDownBkD')	
		}
	}
	//색위치바꾸기
	,"chageBkD":function(isUp){
		var c0 = this.tdsInBkD[0].className;
		var c1 = this.tdsInBkD[1].className;
		var c2 = this.tdsInBkD[2].className;
		if(isUp){
			this.tdsInBkD[0].className = c1;
			this.tdsInBkD[1].className = c2;
			this.tdsInBkD[2].className = c0;
		}else{
			this.tdsInBkD[0].className = c2;
			this.tdsInBkD[1].className = c0;
			this.tdsInBkD[2].className = c1;
		}
	}
	,"colorNextBkD":function(c0,c1,c2){
		this.tdsInNextBkD[0].className = 'bk_ bk_'+c0;
		this.tdsInNextBkD[1].className = 'bk_ bk_'+c1;
		this.tdsInNextBkD[2].className = 'bk_ bk_'+c2;
	}
	,"hideBkD":function(isShow){
		this.ableDrop = isShow;
		this.bkD.style.display = (isShow)?'block':'none';
	}
	,"initNextBkD":function(){
		var c0 = Math.floor(Math.random()*this.colorRange*this.colorRange%this.colorRange);
		var c1 = Math.floor(Math.random()*this.colorRange*this.colorRange%this.colorRange);
		var c2 = Math.floor(Math.random()*this.colorRange*this.colorRange%this.colorRange);
		this.colorNextBkD(c0,c1,c2)
	}	
	,"syncColorBkd":function(){
		this.tdsInBkD[0].className = this.tdsInNextBkD[0].className;
		this.tdsInBkD[1].className = this.tdsInNextBkD[1].className;
		this.tdsInBkD[2].className = this.tdsInNextBkD[2].className;
	}
	,"initBkD":function(){
		this.moveToBkD(Math.floor((this.tdsLength-1)/2)*this.blockWidth,-1*this.blockWidth);
		this.syncColorBkd();
		this.initNextBkD();
	}
	,"clearTds":function(){
		for(var i=0,m=this.tds.length;i<m;i++){
			//this.tds[i].innerHTML = '<div>'+i+'</div>';
			this.tds[i].className = '';
		}
	}
	,"gameOver":function(){
		if(this.playing){
			this.stop();
			this.colorTable('X',1);
			alert('GAME OVER\nThanks for play.');
			if(this.ongameover!=null) this.ongameover();

			return true;
		}
		return false;
	}
	,"checkGameOver":function(){
		for(var i=0,m=this.tdsLength*3;i<m;i++){
			if(this.tds[i].className != ''){
				return this.gameOver();
			}
		}
		return false;
	}
	//-- 레벨업
	,"checkLevelup":function(){
		var t = Math.floor(this.scoreTds/30)+1;
		if(t != this.level){
			this.level = t;
			this.colorRange = 4+((this.level-1)%3);
			this.clearTds();
			this.onchangescore();
			this.consoleLog("level : "+this.level);
			this.consoleLog("colorRange : "+this.colorRange);
			var ct = (this.level-1) % 4;
			this.consoleLog("colorTable : "+ct);
			this.colorTable(ct);
			this.initNextBkD();
		}
		var t = this.initTermTimmer / this.level;
		if(this.termTimmer != t){
			this.termTimmer = t;
			this.consoleLog("termTimmer : "+this.termTimmer);
		}
		
	}
	,"checkLeaveBkD":function(){
		var ts = this.posBkD();
		if(ts[1]%this.blockWidth !==0){return false;}
		var topB = (ts[1] / this.blockWidth); 
		var leftB = (ts[0] / this.blockWidth);
		//--------
		if(this.trsTds.length-3 == topB
		|| (this.trsTds.length>=topB && this.trsTds[topB+3][leftB].className !='')){
			if(topB<0){
				this.checkGameOver();
				return true;
			}
			this.trsTds[topB][leftB].className = this.tdsInBkD[0].className;
			this.trsTds[topB+1][leftB].className = this.tdsInBkD[1].className;
			this.trsTds[topB+2][leftB].className = this.tdsInBkD[2].className;
			this.checkRemoveTds();
			return true;
		}
		return false;
	}
	//-- 사라질 블럭처리
	,"checkRemoveTds":function(multi){
		if(!multi) multi = 1;
		var trsTds = this.trsTds;
		var tds = this.tds;
		var tr,td,rArr=[];
		for(var i=0,m=tds.length;i<m;i++){
			td = tds[i];
			if(td.className != ''){
				var t = this.relTds(i);
				if(t.length>0){
					rArr = rArr.concat(t);
				}
			}
		}

		if(rArr.length>0){
			
			//--- 유니크 처리
			rArr = rArr.sort(function (a, b) { return a - b; });
			var ret = [rArr[0]];
			for (var i=1 , m=rArr.length; i < m; i++) {
				if (rArr[i-1] !== rArr[i]) {
					ret.push(rArr[i]);
				}
			}
			for(var i=0,m=ret.length;i<m;i++){
				var t = tds[ret[i]].className.replace( /[^\d]/g ,'');
				if(t.length>0){
					t = parseInt(t);
					this.scoreBks[this.colorTbl*6+t]++;
					this.consoleLog("scoreBks:"+this.colorTbl+":"+t+":+1");
				}
				tds[ret[i]].className ='bk_r';
			}
			var fn = function(thisC,ret,multi){ 
				return function(){ thisC.removeTds(ret,multi);  }
			}(this,ret.slice(0),(multi));
			//this.stopStep();
			this.addStep(fn,200,1,'removeTds');
			this.addStep(this.nodo,500,1,'nodo');
			this.hideBkD(0);
			//this.startStep();
		}else{
			this.addStep(this.nodo,200,1,'nodo');
			this.checkLevelup();
			this.hideBkD(1);
			this.initBkD();	

			this.checkGameOver();
		}
	}
	,"removeTds":function(ret,multi){
//		alert('x');
		if(!multi) multi = 1;
		var trsTds = this.trsTds;
		var tds = this.tds;
		var tr,td;
		//--- 유니크 처리와 블록 지우기
		if(ret.length>0){
			//--- 블록 지우기
			for(var i=0 , m=ret.length; i < m; i++){
				tds[ret[i]].className = '';
			}
			//--- 빈칸 채우기내리기
			for(var i=0 , m=ret.length; i < m; i++){
				var iTd = ret[i];
				iTd-=this.tdsLength
				while(iTd>=0){
					var td = tds[iTd];
					if(td.className !=''){
						tds[iTd+this.tdsLength].className=td.className;
						td.className = '';
					}else{
						break;
					}
					iTd-=this.tdsLength
				}
			}

			this.addScore(ret.length,multi);
			this.onchangescore();
			this.checkRemoveTds(multi*2);
			 //계속 체크 없을때까지
		}else{
			this.checkGameOver();
		}

		return 0
	}
	,"addScore":function(num,multi){

			this.scoreTds += num;
			var t = (num*100 + (num-3)*10)*multi;
			this.score += t;
			this.consoleLog("addScore : "+num+","+multi+","+t);
	}
	//-- 연관 블럭 찾기
	,"relTds":function(iTd){
		var trsTds = this.trsTds;
		var tds = this.tds;
		var td = tds[iTd];


		var cl = td.className;
		var ts = [];
		var rArr = [];
		if(td.className ==''){ return rArr }
		//-- 4 바향으로 연관 블럭 찾기, 2개이상
		var r = Math.floor(iTd/this.tdsLength), d = iTd%this.tdsLength;
		var wh_i = 0;
		while(wh_i<4){
			ts = [];
			r2 = r,d2=d;
			while(1){
				switch(wh_i){
					case 0:d2++;break;
					case 1:r2++; d2++;break;
					case 2:r2++;break;
					case 3:r2++; d2--;break;
				}
				if(r2<0 || r2>=trsTds.length ||d2<0 || d2 >=this.tdsLength){break}
				if(trsTds[r2][d2].className != cl){ break;}
				ts.push(r2*this.tdsLength+d2);
			}
			if(ts.length>=2){
				rArr.push(r*this.tdsLength+d);
				rArr = rArr.concat(ts);
			}
			wh_i++;
		}

		return rArr;
	}
};