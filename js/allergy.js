function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    var modal = document.getElementById(id);
    modal.style.display = 'none';
    if (id === 'editAllergyModal') {
        var form = document.getElementById('editAllergyForm');
        if (form) form.reset();
        var allergenOther = document.getElementById('editAllergenOtherInput');
        var reactionOther = document.getElementById('editReactionOtherInput');
        if (allergenOther) { allergenOther.style.display = 'none'; allergenOther.required = false; allergenOther.value = ''; }
        if (reactionOther) { reactionOther.style.display = 'none'; reactionOther.required = false; reactionOther.value = ''; }
    }
}
document.addEventListener('DOMContentLoaded', function() {
    ['allergyModal', 'editAllergyModal'].forEach(function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) closeModal(id);
            });
        }
    });
    // Add form handlers for add/edit
    var addForm = document.getElementById('addAllergyForm');
    if (addForm) {
        addForm.onsubmit = function(e) {
            var allergenSel = document.getElementById('allergenSelect');
            var allergenOther = document.getElementById('allergenOtherInput');
            var allergenFinal = document.getElementById('allergenFinal');
            var reactionSel = document.getElementById('reactionSelect');
            var reactionOther = document.getElementById('reactionOtherInput');
            var reactionFinal = document.getElementById('reactionFinal');
            allergenFinal.value = (allergenSel.value === 'Others') ? allergenOther.value.trim() : allergenSel.value;
            reactionFinal.value = (reactionSel.value === 'Others') ? reactionOther.value.trim() : reactionSel.value;
            if (!allergenFinal.value || !reactionFinal.value) {
                alert('Please fill out all required fields.');
                return false;
            }
            return true;
        };
    }
    var editForm = document.getElementById('editAllergyForm');
    if (editForm) {
        editForm.onsubmit = function(e) {
            var allergenSel = document.getElementById('editAllergenSelect');
            var allergenOther = document.getElementById('editAllergenOtherInput');
            var allergenFinal = document.getElementById('editAllergenFinal');
            var reactionSel = document.getElementById('editReactionSelect');
            var reactionOther = document.getElementById('editReactionOtherInput');
            var reactionFinal = document.getElementById('editReactionFinal');
            allergenFinal.value = (allergenSel.value === 'Others') ? allergenOther.value.trim() : allergenSel.value;
            reactionFinal.value = (reactionSel.value === 'Others') ? reactionOther.value.trim() : reactionSel.value;
            if (!allergenFinal.value || !reactionFinal.value) {
                alert('Please fill out all required fields.');
                return false;
            }
            return true;
        };
    }
});
function toggleOtherInput(selectElem, inputId) {
    var input = document.getElementById(inputId);
    if (selectElem.value === 'Others') {
        input.style.display = 'block';
        input.required = true;
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
function openEditAllergyModal(allergy) {
    openModal('editAllergyModal');
    document.getElementById('editAllergyId').value = allergy.id || '';
    var allergenSel = document.getElementById('editAllergenSelect');
    var allergenOther = document.getElementById('editAllergenOtherInput');
    var allergenFinal = document.getElementById('editAllergenFinal');
    var foundAllergen = false;
    for (var i = 0; i < allergenSel.options.length; i++) {
        if (allergenSel.options[i].value === allergy.allergen) {
            allergenSel.selectedIndex = i;
            foundAllergen = true;
            break;
        }
    }
    if (!foundAllergen && allergy.allergen) {
        allergenSel.value = 'Others';
        allergenOther.style.display = 'block';
        allergenOther.value = allergy.allergen;
        allergenOther.required = true;
    } else {
        allergenOther.style.display = 'none';
        allergenOther.value = '';
        allergenOther.required = false;
    }
    allergenFinal.value = allergy.allergen || '';
    var reactionSel = document.getElementById('editReactionSelect');
    var reactionOther = document.getElementById('editReactionOtherInput');
    var reactionFinal = document.getElementById('editReactionFinal');
    var foundReaction = false;
    for (var j = 0; j < reactionSel.options.length; j++) {
        if (reactionSel.options[j].value === allergy.reaction) {
            reactionSel.selectedIndex = j;
            foundReaction = true;
            break;
        }
    }
    if (!foundReaction && allergy.reaction) {
        reactionSel.value = 'Others';
        reactionOther.style.display = 'block';
        reactionOther.value = allergy.reaction;
        reactionOther.required = true;
    } else {
        reactionOther.style.display = 'none';
        reactionOther.value = '';
        reactionOther.required = false;
    }
    reactionFinal.value = allergy.reaction || '';
    document.getElementById('editSeverity').value = allergy.severity || '';
    setTimeout(function() {
        var modal = document.getElementById('editAllergyModalContent');
        if (window.innerWidth < 500) {
            modal.style.padding = '1em 0.5em';
            modal.style.maxWidth = '98vw';
        } else {
            modal.style.padding = '2em 2.5em';
            modal.style.maxWidth = '400px';
        }
    }, 10);
}
function editToggleOtherInput(selectElem, inputId) {
    toggleOtherInput(selectElem, inputId);
}
