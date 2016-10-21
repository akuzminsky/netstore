function confirmLink(theLink, theSqlQuery)
{
    // Confirmation is not required in the configuration file
    if (confirmMsg == '') {
        return true;
    }

    return confirm(confirmMsg + ' :\n' + theSqlQuery);
}

function checkAddUser()
{
 var the_form = document.forms['addUserForm'];
 if(the_form.elements['nopass'][0].checked){
 	return true;
	}
 if (the_form.elements['pw1'].value != the_form.elements['pw2'].value){
 	alert("The passwords aren't same");
	the_form.elements['pw1'].value  = '';
	the_form.elements['pw2'].value  = '';
	the_form.elements['pw1'].focus();
	return false;
	}
}

function setCheckboxes(the_form, cluster_id, contract_id)
{
		//window.alert('Cluster ' + cluster_id + ' contract ' + contract_id);
    var elts      = document.forms[the_form].elements['cl_[' + cluster_id + '][]'];
    var elts_cnt  = (typeof(elts.length) != 'undefined')
                  ? elts.length
                  : 0;

			//	window.alert(elts.length);
    if (elts_cnt) {
        for (var i = 0; i < elts_cnt; i++) {
        	elts[i].checked = ! elts[i].checked;
        } // end for
    } else {
				elts.checked = ! elts.checked;
    } // end if... else

    return true;
} // end of the 'setCheckboxes()' function

function setAllCheckboxes(the_form)
{
		//window.alert('Cluster ' + cluster_id + ' contract ' + contract_id);
    var elts      = document.forms[the_form].elements['cl_[]'];
    var elts_cnt  = (typeof(elts.length) != 'undefined')
                  ? elts.length
                  : 0;

			//	window.alert(elts.length);
    if (elts_cnt) {
        for (var i = 0; i < elts_cnt; i++) {
        	elts[i].checked = ! elts[i].checked;
        } // end for
    } else {
				elts.checked = ! elts.checked;
    } // end if... else

    return true;
} // end of the 'setCheckboxes()' function

