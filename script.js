var name="";
var recorder;
var players={};
var stopped=true;
//var recordedchunks;
var sequenceid=0;
var barred=[];
function inspect(obj,showinherited,searchdepth,depth) {
  if (showinherited===undefined)
    showinherited=true;
  if (depth===undefined)
    depth=0;
  if (searchdepth===undefined)
    searchdepth=2;
  var log="[--"+depth+"\n",decal=" ";
  if (depth>searchdepth)
    return "[MAX DEPTH!]\n";
  for (var i=0;i<depth;i++) {
    decal+=" ";
  }
  var count=0;
  for (var p in obj) {
    var t = typeof obj[p];
    if (obj.hasOwnProperty(p)) {
      if (p!=="innerHTML" && p!=="outerHTML") {
        log+=decal+"."+p+"("+t+")="+(t === "object" ? "\n"+inspect(obj[p],showinherited,searchdepth,depth+1) : obj[p])+"\n";
      }
    } else {
      if (showinherited) {
        if (p!=="innerHTML" && p!=="outerHTML") {
	  log+=decal+"(h)."+p+"("+t+")="+ (t === "object" ? "\n"+inspect(obj[p],showinherited,searchdepth,depth+1) : obj[p])+"\n";
        }
      }
    }
    count++;
  }
  return log+depth+"--]\n";
}
function getrandom() {
  return Math.round(Math.random()*10000000000);
}
function getfromsid(sid) {
  for(var rid in players) {
    if (players[rid]["sid"]===sid) {
      return rid;
    }
  }
  return false;
}
function display(text,received) {
  if (received===undefined)
    received=0;
  if (received===0 || received===2 || received===3) {
    document.querySelector('.message').innerHTML+=text.replace(new RegExp('\n','g'),"<BR>")+"<BR>";
  } else if (received===1) {
    document.querySelector('.received').innerHTML=(Number(text)+Number(document.querySelector('.received').innerHTML));    
  } else if (received===2) {
    document.querySelector('.events').innerHTML+=text.replace(new RegExp('\n','g'),"<BR>")+"<BR>";
  } else if (received===3) {
    document.querySelector('.network').innerHTML+=text.replace(new RegExp('\n','g'),"<BR>")+"<BR>";
  }  
}
function stopall(closing) {
  if (closing===undefined) {
    closing=false;
  }
  display("stopall");
  stopped=true;
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/audio/receiveupdates.php');
  var formdata = new FormData();
  if (closing)
    formdata.append("close",name);
  else
    formdata.append("stop",name);
  xhr.send(formdata);  
  try {
    recorder.stream.getAudioTracks()[0].stop();
    recorder.stop();    
  } catch(e) {
    display(e.message);
  }
  for(var rid in players) {
    removeplayer(rid);
  }
}
function precisionRound(number, precision) {
  var factor = Math.pow(10, precision);
  var retval=""+Math.round(number * factor) / factor;
  if (retval.indexOf(".")>0) {
    if (retval.substr(retval.indexOf(".")+1).length<precision) {
      for(var i=0;i<=precision-retval.substr(retval.indexOf(".")+1).length;i++)
        retval+="0";
    } else {
        retval=retval.substr(0,retval.indexOf(".")+1)+retval.substr(retval.indexOf(".")+1,precision);
    }
  } else {
    if (precision>number.length)
      for(var i=0;i<precision-number.length;i++)
        retval="0"+retval;
  }
  return retval;
}
function registersourcebuffer(buffer,rid) {
  var firstpass=true;
  buffer.onabort=function() {
    display("<font color='green'>abort "+rid+"</font>",2);
  };
  buffer.onerror=function(e) {
    display("<font color='green'>error "+rid+"</font>",2);    
    if (players[rid]!==undefined) {
      if (players[rid]["sb"]!==0) {
        sendremotereset(players[rid]["sid"]);      
      }      
      var audioposition="NA";
      if(players[rid]["audio"]!==undefined) {
        audioposition=players[rid]["audio"].currentTime;
      }
      var available="";
      for (var p in players[rid]["pending"]) {
        available+=" "+p[0];
      }
      if (this.buffered!==undefined) {
        if (this.buffered.length>0) {
          display("BE cur pos: "+players[rid]["current"][0]+" pend:("+players[rid]["pending"].length+")"+available+" buff["+buffer.buffered.start(0)+"-"+buffer.buffered.end(0)+"] player:"+audioposition,3);
          return;
        } 
      }
      display("BE cur pos: "+players[rid]["current"][0]+" pend:("+players[rid]["pending"].length+")"+available+" player:"+audioposition,3);
    }
  };
  buffer.onupdate=function() {
    //display("<font color='green'>update "+rid+"</font>",2);
  };
  buffer.onupdateend=function() {
    //display("<font color='green'>updateend "+rid+"</font>",2);
    try {            
      /*
      if (players[rid]["min"]===undefined) {
        players[rid]["min"]=this.buffered.end(0)-players[rid]["audio"].currentTime;
        players[rid]["max"]=this.buffered.end(0)-players[rid]["audio"].currentTime;
        players[rid]["count"]=0;
      } else {
        players[rid]["count"]+=1;
        if (players[rid]["count"]>20) {
          players[rid]["count"]=0;
          players[rid]["min"]=this.buffered.end(0)-players[rid]["audio"].currentTime;
          players[rid]["max"]=this.buffered.end(0)-players[rid]["audio"].currentTime;            
        }
        if (players[rid]["min"]>this.buffered.end(0)-players[rid]["audio"].currentTime)
          players[rid]["min"]=this.buffered.end(0)-players[rid]["audio"].currentTime;    
        if (players[rid]["max"]<this.buffered.end(0)-players[rid]["audio"].currentTime)
          players[rid]["max"]=this.buffered.end(0)-players[rid]["audio"].currentTime;    
      }*/
      
      document.querySelector('.timediff_'+rid).innerHTML=precisionRound(this.buffered.end(0)-players[rid]["audio"].currentTime,2); //+" "+precisionRound(players[rid]["min"],2)+" "+precisionRound(players[rid]["max"],2)
      document.querySelector('.ptime_'+rid).innerHTML=precisionRound(this.buffered.end(0),2);
    } catch(e) {}
    if (firstpass) {
      if (players[rid]!==undefined) {
        if (this.buffered!==undefined) {
          if (this.buffered.length>0) {
            if (mobile) {
              if (this.buffered.end(0)-0.4>0) {
                players[rid]["audio"].currentTime=this.buffered.end(0)-0.4;
                firstpass=false;
              }                              
            } else {
              if (this.buffered.end(0)-0.01>0) {
                players[rid]["audio"].currentTime=this.buffered.end(0)-0.01;
                firstpass=false;
              }
            }
          }          
        }
      }    
    }
    if (players[rid]!==undefined) {
      if (players[rid]["pending"].length>0)
        setTimeout(addtoplayer,5,this,rid);
    }      
  };
  buffer.onupdatestart=function() {
    //display("<font color='green'>updatestart "+rid+"</font>",2);
    //if (players[rid]["track"].sourceBuffers[0].buffered.end(0)-players[rid]["audio"].currentTime>0.7) {
    //  players[rid]["audio"].currentTime=players[rid]["track"].sourceBuffers[0].buffered.end(0)-0.3;
    //}          
    if (true) {  // mobile
      if (this.buffered!==undefined && this.buffered!==null) {
        if (this.buffered.length>0) {
          var x=this.buffered.end(0)-players[rid]["audio"].currentTime;
          var t=0.5;
          var d=0;
          if (x>t+d) {
            var n=1+((x-t) * (x-t));
            if (n>1.3) {
              n=1.3;
            }
            players[rid]["audio"].playbackRate=n;
          }     
          if (x<t-d){
            var n=1+((t-x) * (x-t));
            if (n<1) {
              n=1;
            }
            players[rid]["audio"].playbackRate=n;
          }        
        }
      } else {
        players[rid]["audio"].playbackRate=1;
      }
    }
    
//    if (this.buffered.end(0)-players[rid]["audio"].currentTime>1) {      
//      if (players[rid]["audio"].playbackRate<1.1) {
//        players[rid]["audio"].playbackRate+=0.005;
//        display("playspeed: "+players[rid]["audio"].playbackRate);          
//      }
//    } 
  };
}
function createplayer(rid) {
    if (players[rid]===undefined)
      return;
    var sid=players[rid]["sid"];
    players[rid]["status"]=0;
    display("create player "+rid);
    var ms=new MediaSource();
    players[rid]["track"]=ms;
    ms.addEventListener('sourceopen', function() {
      //display("<font color='red'>sourceopen "+rid+"</font>",2);
    });
    ms.addEventListener('sourceended', function() {
      //display("<font color='red'>sourceended "+rid+"</font>",2);
    });
    ms.addEventListener('sourceclose', function() {
      //display("<font color='red'>sourceclose "+rid+"</font>",2);
    });
    var div = document.createElement('div');
    div.setAttribute("class","player");
    div.setAttribute("id",sid);
    var audio = document.createElement('audio');
    audio.controls = false;
    audio.autoplay = true;
    audio.type = 'audio/webm;codecs="opus"';
    var name=document.createElement("A");
    name.href="#";
    name.setAttribute("class","namelink playername_"+rid);
    players[rid]["style"]="";
    players[rid]["sb"]=1;
    name.onclick = function(e) {
      e.preventDefault();
      players[rid]["audio"].muted=!players[rid]["audio"].muted;
      if (players[rid]["audio"].muted) {
        players[rid]["style"]=this.getAttribute("style");
        this.setAttribute("style","color:grey;");
      } else {
        this.setAttribute("style",players[rid]["style"]);
      }
    };
    name.appendChild(document.createTextNode(players[rid]["name"]));
    div.appendChild(name);    
    audio.onloadedmetadata = function() {
      //display("<font color='blue'>loadedmetadata "+rid+"</font>",2);
    };
    audio.onloadeddata = function() {
      //display("<font color='blue'>loadeddata "+rid+"</font>",2);      
    };    
    audio.onprogress = function() {
      //display("<font color='blue'>progress "+rid+"</font>",2);
    };    
    audio.onplaying = function() {
      display("<font color='blue'>playing "+rid+"</font>",2);
      try {
        players[rid]["style"]=document.querySelector(".playername_"+rid).getAttribute("style");
        var style=document.querySelector(".playername_"+rid).getAttribute("style");
        if (style==="color:grey;") {
          players[rid]["style"]="color:green;";
        } else {
          document.querySelector(".playername_"+rid).setAttribute("style","color:green;");
        }          
      } catch(e) {          
      }
      /*if (players[rid]["status"]>1) {
        if (players[rid]["track"].sourceBuffers[0].buffered.end(0)-players[rid]["audio"].currentTime>0.5) {
          players[rid]["audio"].currentTime=players[rid]["track"].sourceBuffers[0].buffered.end(0)-0.2;
        }          
      }*/
      players[rid]["status"]=1;
    };    
    audio.onerror = function() {
      display("<font color='blue'>error "+rid+"</font>",2);
      if (players[rid]!==undefined) {
        if (players[rid]["sb"]!==1) {
          sendremotereset(players[rid]["sid"]);      
        }         
      }
      try {
        var style=document.querySelector(".playername_"+rid).getAttribute("style");
        if (style==="color:grey;") {
          players[rid]["style"]="";
        } else {
          document.querySelector(".playername_"+rid).setAttribute("style","");
        }          
      }catch(e) {          
      }
      display("player at: "+this.currentTime+" EC:"+this.error.code+" EM:"+this.error.message);
    };    
    audio.onpause = function() {
      display("<font color='blue'>pause "+rid+"</font>",2);
    };
    var firstpass=true;
    audio.oncanplay = function() {
//      if (players[rid]["sb"]!==1) {
//        if (audio.buffered!==undefined)
//          if (audio.buffered.end(0)>4 && firstpass) {
//            audio.currentTime=audio.buffered.end(0)-4;
//            firstpass=false;
//          }            
//      }
      display("<font color='blue'>canplay "+rid+"</font>",2);
      audio.play();      
    };
    audio.onabort = function() {
      display("<font color='blue'>abort "+rid+"</font>",2);
      try {
        var style=document.querySelector(".playername_"+rid).getAttribute("style");
        if (style==="color:grey;") {
          players[rid]["style"]="";
        } else {
          document.querySelector(".playername_"+rid).setAttribute("style","");
        }          
      }catch(e) {          
      }
      
      //removeplayer(rid);
    };
    audio.onended = function() {
      display("<font color='blue'>ended "+rid+"</font>",2);
    };
    audio.onstalled = function() {
      display("<font color='blue'>stalled "+rid+"</font>",2);
      try {
        var style=document.querySelector(".playername_"+rid).getAttribute("style");
        if (style==="color:grey;") {
          players[rid]["style"]="";
        } else {
          document.querySelector(".playername_"+rid).setAttribute("style","");
        }          
      }catch(e) {          
      }
      players[rid]["status"]=3;
      //removeplayer(rid);
    };
    audio.onsuspend = function() {
      display("<font color='blue'>suspend "+rid+"</font>",2);
    };       
    audio.onwaiting = function() {
      display("<font color='blue'>waiting "+rid+"</font>",2);
      players[rid]["status"]=2;
      //removeplayer(rid);
    };
    players[rid]["audio"]=audio;
    div.appendChild(audio);
    var div1 = document.createElement('div');
    div1.setAttribute("class","timedisp timediff_"+rid);
    var div2 = document.createElement('div');
    div2.setAttribute("class","timedisp ptime_"+rid);
    var div3 = document.createElement('div');
    div3.setAttribute("class","timedisp btime_"+rid);
    var div4 = document.createElement('div');
    div4.setAttribute("class","timedisp netrt_"+rid);
    div.appendChild(div1);
    div.appendChild(div2);
    div.appendChild(div3);
    div.appendChild(div4);
    document.querySelector('.players').appendChild(div);
    audio.src = window.URL.createObjectURL(ms);
}
function isbarred(sid) {
  for(var i=0;i<barred.length;i++) {
    if (barred[i]===sid) return true;
  }
  return false;
}
function removeplayer(rid) {    
  display("remove player "+rid);
  if (players[rid]!==undefined) {
    var sid=players[rid]["sid"];
    if (players[rid]["track"]!==undefined) {
      players[rid]["track"].onsourceopen=null;
      if (Object.keys(players[rid]["track"].sourceBuffers).length>0) {
        if (players[rid]["track"].readyState==="open") {
          players[rid]["track"].sourceBuffers[0].abort();
          players[rid]["track"].removeSourceBuffer(players[rid]["track"].sourceBuffers[0]);            
        }
      }
      delete players[rid]["track"];
    }
    if (players[rid]["audio"]!==undefined) {
      players[rid]["audio"].pause();
      players[rid]["audio"].oncanplay=null;
      players[rid]["audio"].onstalled=null;
      players[rid]["audio"].src="";
      delete players[rid]["audio"];
    }
    delete players[rid];
    var div=document.getElementById(sid);
    if (div!==null)
      div.parentNode.removeChild(div);
    
  }   
}
function permanentremove(rid) {
  if (players[rid]!==undefined) {
    var sid=players[rid]["sid"];
    removeplayer(rid);
    barred.push(sid);     
  }
}
function startlistening() {
  document.querySelector('.players').innerHTML="";
  for (var rid in players) {
    if (!isbarred(players[rid]["sid"]))
      createplayer(rid);
  }
  getaudiotracks();
}
function addtoplayer(buffer,rid) {
  if (players[rid]!==undefined) {
    if (players[rid]["pending"]!==undefined)
      if (players[rid]["pending"].length>0)
        if (buffer!==undefined)
          if (!buffer.updating) {
            var position=0;
            var length=0;
            if (players[rid]["current"]!==undefined) {
              position=players[rid]["current"][0];
              length=players[rid]["current"][1];
              for(var i=players[rid]["pending"].length-1;i>=0;i--) {
                if (players[rid]["pending"][i][0]<position+length) {
                  players[rid]["pending"].splice(i,1);
                } else if (players[rid]["pending"][i][0]===position+length) {
                  players[rid]["current"]=players[rid]["pending"][i];
                  players[rid]["pending"].splice(i,1);
                  buffer.appendBuffer(new Uint8Array(players[rid]["current"][2]));                  
                  //display("added slice:"+(position+length)+" next slice:"+(players[rid]["current"][0]+players[rid]["current"][1])+" pending:("+players[rid]["pending"].length+") buffer["+buffer.buffered.start(0)+"-"+buffer.buffered.end(0)+"]",3);
                  return;
                }
              }           
              gettrackdata(rid,0,position+length);
              
              if (buffer.buffered!==undefined)
                if (buffer.buffered.length>0) {
                  var available="";
                  for(var i=0;i<players[rid]["pending"].length;i++) {
                    available+=" "+players[rid]["pending"][i][0];
                  }
                  var playtime="";
                  try {
                    playtime="player at "+players[rid]["audio"].currentTime;
                  } catch(e) {
                    display(e.message);
                  }
                  //display("missing slice "+(position+length)+" pending:("+players[rid]["pending"].length+")"+available+" buffer["+buffer.buffered.start(0)+"-"+buffer.buffered.end(0)+"] "+playtime,3);
                } 
              
            } else {
              for(var i=0;i<players[rid]["pending"].length;i++) {
                if (players[rid]["pending"][i][0]===0) {
                  players[rid]["current"]=players[rid]["pending"][i];
                  players[rid]["pending"].splice(i,1);
                  buffer.appendBuffer(new Uint8Array(players[rid]["current"][2]));
                  position=players[rid]["current"][0];
                  length=players[rid]["current"][1];
                  //display("added slice:"+position+" next slice:"+(position+length)+" pending:("+players[rid]["pending"].length+")",3);
                  return;
                }
              }            
            }
          }
  }
}

var retrievestats={};
var lastget=0;

function gettrackdata(rid,retry,seekposition,length) {
  if (retry===undefined)
    retry=0;
  if (length===undefined)
    length=0;
  if (seekposition===undefined)
    seekposition=-1;
  if (players[rid]===undefined || retry>2)
    return;
  var sid=players[rid]["sid"];
  var t0 = new Date().getTime();
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/audio/sendaudio.php');
  xhr.responseType = 'arraybuffer';  
  var formdt = new FormData();
  if (length!==0)
    formdt.append('Get-Bytes', length);
  if (seekposition!==-1)
    formdt.append('Stream-Position', seekposition);
  formdt.append("ID",sid);
  formdt.append('playerid', rid);
  xhr.onerror = function() {
    var cur=(new Date().getTime())-t0;    
    try {      
      if (retrievestats[rid]===undefined) {
        retrievestats[rid]=[];
      } else if (retrievestats[rid].length>9){
        retrievestats[rid].splice(0,retrievestats[rid].length-9);
      }
      retrievestats[rid].push(cur);
      var total=0;
      for(var i=0;i<retrievestats[rid].length;i++) {
        total+=retrievestats[rid][i];
      }
      document.querySelector(".netrt_"+rid).setAttribute("style","color:purple;");
      document.querySelector(".netrt_"+rid).innerHTML=precisionRound(total/(retrievestats[rid].length*1000),3);
    } catch (e) {}
    gettrackdata(rid,++retry);
  };
  xhr.onload = function() {    
    if (lastget!==0) {
      var diff=(new Date().getTime())-lastget;
      lastget=new Date().getTime();
      document.querySelector('.btime_'+rid).innerHTML=diff;
    } else {
      lastget=new Date().getTime();
    }
    try {      
      var cur=(new Date().getTime())-t0;
      if (retrievestats[rid]===undefined) {
        retrievestats[rid]=[];
      } else if (retrievestats[rid].length>9){
        retrievestats[rid].splice(0,retrievestats[rid].length-9);
      }
      retrievestats[rid].push(cur);
      var total=0;
      for(var i=0;i<retrievestats[rid].length;i++) {
        total+=retrievestats[rid][i];        
      }
      if (xhr.status===200) {          
        document.querySelector(".netrt_"+rid).setAttribute("style","color:blue;");
      } else {
        document.querySelector(".netrt_"+rid).setAttribute("style","color:red;");
      }
      document.querySelector(".netrt_"+rid).innerHTML=precisionRound(total/(retrievestats[rid].length*1000),3);
    } catch (e) {}
    if (players[rid]===undefined)
      return;
    var size=0,position=0;
    try {
      size=Number(xhr.getResponseHeader("Content-Length"));
      position=Number(xhr.getResponseHeader("Stream-Position"));
    } catch(e) {
      display("gettrack number format error "+e.message);
      return;
    }
    if (xhr.status===200 && size>0) {
      if (players[rid]!==undefined) {
        if (players[rid]["contenttype"]===undefined) {
          players[rid]["contenttype"]=xhr.getResponseHeader("Content-Type");
        }
        if (players[rid]["pending"]===undefined) {
          players[rid]["pending"]=[];
        }        
        var ms=players[rid]["track"];
        if (ms!==undefined) {
          if (Object.keys(ms.sourceBuffers).length===0) {
            try {
              if (ms.readyState==="open") {
                if (players[rid]["contenttype"]!==undefined) {
                  //display("add buffer for "+rid+": "+players[rid]["contenttype"]);
                  ms.addSourceBuffer(players[rid]["contenttype"]);
                  registersourcebuffer(ms.sourceBuffers[0],rid);               
                }                
              }
            } catch (e) {
              display("AddSourceBufferFailed: "+e.message+" for sid:"+sid);
              players[rid]["sb"]=0;
              var base=""+window.location;
              base=base.substring(0,base.lastIndexOf("/")+1);
              players[rid]["audio"].type=players[rid]["contenttype"];
              players[rid]["audio"].src=base+"sendaudio.php?SB=0&ID="+sid+"&playerid="+rid;
              //permanentremove(rid);
              return;
            }
          }
        }
        players[rid]["pending"].push([position,size,xhr.response]);
        //display("received for "+rid+" "+size+" bytes at "+position,3);
        setTimeout(addtoplayer,5,ms.sourceBuffers[0],rid);
        //addtoplayer(ms.sourceBuffers[0],rid);        
      }
      
    } else {
      if (xhr.status!==204) {
        display("failed request "+xhr.status);
        gettrackdata(rid,++retry);
      }
    }
  };
  xhr.send(formdt);  
}
function getaudiotracks() {
  if (stopped)
    return;
  for (var rid in players) {
    if (players[rid]["sb"]===1)
      gettrackdata(rid);
  }
  if (mobile)
    setTimeout(getaudiotracks,200);
  else
    setTimeout(getaudiotracks,60);
}
function updateplayerslist() {
  if (stopped)
    return;
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/audio/receiveupdates.php');
  var formdata = new FormData();
  formdata.append("update",name);
  xhr.send(formdata);
  xhr.onload = function() {
    if (stopped)
      return;
    if (xhr.status === 200) {
      var ids=[];
      if (xhr.response.length>0) {
        var parts = xhr.response.split("/");
        if (parts[0]==="0") {            
        } else if (parts[0]==="1") {
          display("received reset");
          stopall(true);
          setTimeout(startall,50);
          return;
        } else {
          removeplayer(getfromsid(parts[0]));
        }
        if (parts.length>1) {
          var names=parts[1].split("|");
          for(var i=0;i<names.length;i++) {
            var id=names[i].split(",");
            ids.push(id[0]);            
            if (!getfromsid(id[0])) {
              if (!isbarred(id[0])) {
                var rid=getrandom();
                players[rid]={};
                players[rid]["sid"]=id[0];
                players[rid]["name"]=id[1];
                createplayer(rid);
              }
            }
          }          
        }
      }
      for(var rid in players) {
        var deleteit=true;
        for(var i=0;i<ids.length;i++) {
          if (ids[i]===players[rid]["sid"])
            deleteit=false;
        }
        if (deleteit) {
          removeplayer(rid);
        }
      }
    }
  };
  if (mobile)
    setTimeout(updateplayerslist,2000);
  else
    setTimeout(updateplayerslist,500);
}
var sendtime=[];
var lastsend=0;
function sendchunk(e,seqid,retry) {
  if (retry===undefined)
    retry=0;
  if (retry>2)
    return;
  var t0 = new Date().getTime();
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/audio/receiveaudio.php');
  xhr.setRequestHeader('sequenceid', seqid);
  xhr.setRequestHeader('Content-Type',e.type);
  xhr.onerror = function(){
    if (lastsend!==0) {
      var diff=(new Date().getTime())-lastsend;
      lastsend=new Date().getTime();
      document.querySelector(".sendfreq").setAttribute("style","color:red;");
      document.querySelector('.sendfreq').innerHTML=diff;
    } else {
      lastsend=new Date().getTime();
    }      
    display("resending "+e.size+" "+seqid);    
    var cur=(new Date().getTime())-t0;
    if (sendtime.length>9){
      sendtime.splice(0,sendtime.length-9);
    }
    sendtime.push(cur);
    var total=0;
    for(var i=0;i<sendtime.length;i++) {
      total+=sendtime[i];
    }
    document.querySelector(".sendtime").setAttribute("style","color:red;");
    document.querySelector(".sendtime").innerHTML=precisionRound(total/(sendtime.length*1000),3);
    
    sendchunk(e,seqid,++retry);
  };
  xhr.onload = function() {
    if (lastsend!==0) {
      var diff=(new Date().getTime())-lastsend;
      lastsend=new Date().getTime();
      document.querySelector(".sendfreq").setAttribute("style","color:blue;");
      document.querySelector('.sendfreq').innerHTML=diff;
    } else {
      lastsend=new Date().getTime();
    }      
    if (xhr.status !== 204) {
      var cur=(new Date().getTime())-t0;
      if (sendtime.length>9){
        sendtime.splice(0,sendtime.length-9);
      }
      sendtime.push(cur);
      var total=0;
      for(var i=0;i<sendtime.length;i++) {
        total+=sendtime[i];
      }
      document.querySelector(".sendtime").setAttribute("style","color:red;");
      document.querySelector(".sendtime").innerHTML=precisionRound(total/(sendtime.length*1000),3);
      display("resending "+e.size+" "+seqid);
      sendchunk(e,seqid,++retry);
    } else {
                
      var cur=(new Date().getTime())-t0;
      if (sendtime.length>9){
        sendtime.splice(0,sendtime.length-9);
      }
      sendtime.push(cur);
      var total=0;
      for(var i=0;i<sendtime.length;i++) {
        total+=sendtime[i];
      }
      document.querySelector(".sendtime").setAttribute("style","color:blue;");
      document.querySelector(".sendtime").innerHTML=precisionRound(total/(sendtime.length*1000),3);
      
      display(e.size,1);
    }
  };
  xhr.send(e);
}
function sendremotereset(sid) {
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/audio/receiveupdates.php');
  var formdata = new FormData();
  formdata.append("sendreset",sid);
  xhr.send(formdata);
}
function startrecording() {
  navigator.mediaDevices.enumerateDevices().then(function(devices) {
    devices = devices.filter(function(d) {return d.kind === 'audioinput';});
    navigator.mediaDevices.getUserMedia({audio:{deviceId:devices[0].deviceId,echoCancellation: true,volume:1},video:false}).then(function(stream) {
      var types = ['audio/webm;codecs="opus"','audio/ogg;codecs="opus"'];//["audio/webm;codecs=\"opus\"",'audio/ogg;codecs="opus"'];
      for (var i in types)
        if (MediaRecorder.isTypeSupported(types[i])) {
          //display("recording "+types[i]);
          recorder = new MediaRecorder(stream,{mimeType : types[i]});
          break;
        }
      if (recorder!==undefined) {
        recorder.onerror = function(e) {
          display("recorder error");
          recorder.stop();
        };
        recorder.onstop = function(e) {
//          var blob = new Blob(recordedchunks, { 'type' : 'audio/webm' });
//          var link = document.createElement('a');
//          link.setAttribute('download', "recorded.webm");
//	  link.setAttribute('href',window.URL.createObjectURL(blob));
//          link.click();           
        };                  
        recorder.ondataavailable = function(e) {
            //display(""+(new Date().getTime()));
          if (e.data.size!==0) {
              //recordedchunks.push(e.data);
              setTimeout(sendchunk,5,e.data,sequenceid);
              //sendchunk(e.data,sequenceid);
              sequenceid++;       
          }          
        };
        if (mobile)
          recorder.start(120);
        else
          recorder.start(60);
      } else {
        display("No supported mime type");
      }
    }).catch(function(err) {display("error "+err.name);recorder.stream.getAudioTracks()[0].stop();});
  });       
}
function startall() {
  display("startall");
  if (mobile)
    display("mobile");
  else
    display("desktop");
  if (stopped===false)
    return;
  stopped=false;
  //recordedchunks=[];
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/audio/receiveupdates.php');
  var formdata = new FormData();
  formdata.append("start",name);
  xhr.send(formdata);
  xhr.onerror = function(){
    document.querySelector('input[name="start"]').click();
  };
  xhr.onload = function() {
    if (xhr.status === 200 && xhr.response.length>0) {
      var names=xhr.response.split("|");
      for(var i=0;i<names.length;i++) {
        var id=names[i].split(",");
        var rid=getrandom();
        if (!getfromsid(id[0])) {
          if (!isbarred(id[0])) {
            players[rid]={};
            players[rid]["sid"]=id[0];
            players[rid]["name"]=id[1];              
          }
        }
      }            
    }
    sequenceid=0;
    startrecording();
    startlistening();
    updateplayerslist();
  };
}

document.addEventListener('DOMContentLoaded', function(){
  document.querySelector('input[name="start"]').addEventListener("click", function(){
    if (document.querySelector('input[name="name"]').value.length>0) {
      name=document.querySelector('input[name="name"]').value;
      startall();
    } else {
      alert("Enter a name to start");
    }
  });  
  document.querySelector('input[name="restart"]').addEventListener("click", function(){
    for (var rid in players) {
      removeplayer(rid);          
    }
  });
  document.querySelector('input[name="reset"]').addEventListener("click", function(){
    for (var rid in players) {       
      sendremotereset(players[rid]["sid"]);
      removeplayer(rid);          
    }
  });
  document.querySelector('input[name="stop"]').addEventListener("click", function(){
    stopall();
  });
});
window.onclose = window.onunload = window.onbeforeunload = function () {
  stopall(true);
};


