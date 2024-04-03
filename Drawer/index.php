
<?php
?>

<!DOCTYPE HTML>
<html>
<head>
<style type='text/css'>
#canvas {
 position: absolute;
 width: 800px;
 height: 800px;
 left: 0;
 top: 0px;
 border: 1px ridge #000;
}
#color_pane {
 position: absolute;
 left: 805px;
 top: 10px;
}
#other_tools {
 position: absolute;
 left: 845px;
 top: 10px;
}
#other_tools button {
 float:left;
 clear:both;
 width: 35px;
 height: 35px;
}
</style>
</head>
<body>

<center>
<div id='container'>
<svg width="800" height="800" xmlns="http://www.w3.org/2000/svg" id='canvas' onmousedown='d.draw(event)' onmouseup='d.endDraw()' onmousemove='d.trackMovement(event)' />
</svg>
</div>
<div id='color_pane'>
</div>
<div id='other_tools'>
<button onclick='d.removeLastPath()'>↩️</button>
<button tool='pencil' onclick='d.setTool("pencil")' disabled='true'>✏️</button>
<button tool='rectangle' onclick='d.setTool("rectangle")'>🔲</button>
<button tool='line' onclick='d.setTool("line")'>↔️</button>
<button tool='circle' onclick='d.setTool("circle")'>⭕</button>
<button tool='text' onclick='d.setTool("text")'>💬</button>
<button tool='text' onclick='d.save()'>💾</button>

</div>
</center>

</body>
<script src='Drawer.js' type='text/javascript'></script>
<script type='text/javascript'>
var d = new Drawer();
</script>
</html>
