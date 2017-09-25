// this code snippet stops the user from chosing the same priority twice, which is not allowed.

$( document ).ready(function() {
	$('#il_center_col > form[name="surveyOutput"]').submit(function (e) {

		var i = 0;
		var choices = new Array();

		// loop through all the selects
		$('#il_center_col form[name="surveyOutput"] select').each(function() {

			if(!($(this).val() in choices)){

				// the given priority has not been chosen yet, so it's put into the array choices
				choices[$(this).val()] = i++;

			}else{

				// the given priority has been chosen already.
				// thus the submit-process is being interrupted
				e.preventDefault();

				// change colors for both the current and the select with the same priority
				$(this).css("background-color", "#F75D59");
				$(this).siblings("label").css("color", "red");
				$('#il_center_col form[name="surveyOutput"] select:eq(' + choices[$(this).val()] + ')').css("background-color", "#F75D59");
				$('#il_center_col form[name="surveyOutput"] select:eq(' + choices[$(this).val()] + ')').siblings("label").css("color", "red");
			}

			// as soon as the user clicks on the select again, the warning colors shall be reset.
			$(this).click(function () {
				$('#il_center_col form[name="surveyOutput"] select').siblings("label").css("color", "black");
				$('#il_center_col form[name="surveyOutput"] select').css("background-color", "white");
			});

		});
	});
});


