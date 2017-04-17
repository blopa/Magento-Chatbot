function werulesTogleMatchMode(element)
{
	//debugger;
	var val = element.value;
	var name = element.name;
	if (val == 0)
	{
		name = name.substr(0, name.lastIndexOf("[")) + "[similarity]";
		var target = document.getElementsByName(name);
		target[0].disabled = false;
	}
	else
	{
		name = name.substr(0, name.lastIndexOf("[")) + "[similarity]";
		var target = document.getElementsByName(name);
		target[0].disabled = true;
	}
}

function werulesTogleReplyMode(element)
{
	//debugger;
	var val = element.value;
	var name = element.name;
	if (val == 0)
	{
		name = name.substr(0, name.lastIndexOf("[")) + "[command_id]";
		var target = document.getElementsByName(name);
		target[0].disabled = true;
	}
	else
	{
		name = name.substr(0, name.lastIndexOf("[")) + "[command_id]";
		var target = document.getElementsByName(name);
		//target[0].classList.remove("werules-disabled");
		//target[0].style = "";
		target[0].disabled = false;
	}
}