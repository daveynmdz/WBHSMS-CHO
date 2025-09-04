<div id="allergyModal" class="custom-modal">
    <div class="modal-content">
        <button type="button" class="close-btn" onclick="closeModal('allergyModal')">&times;</button>
        <h3>Allergies</h3>
        <table class="allergy-table">
            <thead>
                <tr>
                    <th>Allergen</th>
                    <th>Reaction</th>
                    <th>Severity</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($medical_history['allergies'])): ?>
                    <?php foreach ($medical_history['allergies'] as $allergy): ?>
                        <tr>
                            <td><?= htmlspecialchars($allergy['allergen']) ?></td>
                            <td><?= htmlspecialchars($allergy['reaction']) ?></td>
                            <td><?= htmlspecialchars($allergy['severity']) ?></td>
                            <td>
                                <button type="button" class="edit-btn" onclick='openEditAllergyModal(<?= htmlspecialchars(json_encode($allergy), ENT_QUOTES, "UTF-8") ?>)'>Update</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="no-records">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Add Allergy Form -->
        <form id="addAllergyForm" method="post" action="add_allergy.php">
            <h4>Add New Allergy</h4>
            <div class="allergy-form-row">
                <div>
                    <select name="allergen_dropdown" id="allergenSelect" required onchange="toggleOtherInput(this, 'allergenOtherInput')">
                        <option value="">Select Allergen</option>
                        <option value="Peanuts">Peanuts</option>
                        <option value="Penicillin">Penicillin</option>
                        <option value="Pollen">Pollen</option>
                        <option value="Shellfish">Shellfish</option>
                        <option value="Eggs">Eggs</option>
                        <option value="Milk">Milk</option>
                        <option value="Latex">Latex</option>
                        <option value="Insect Stings">Insect Stings</option>
                        <option value="Dust Mites">Dust Mites</option>
                        <option value="Mold">Mold</option>
                        <option value="Others">Others (specify)</option>
                    </select>
                    <input type="text" id="allergenOtherInput" placeholder="Specify Allergen" style="display:none;" />
                </div>
                <div>
                    <select name="reaction_dropdown" id="reactionSelect" required onchange="toggleOtherInput(this, 'reactionOtherInput')">
                        <option value="">Select Reaction</option>
                        <option value="Rash">Rash</option>
                        <option value="Anaphylaxis">Anaphylaxis</option>
                        <option value="Swelling">Swelling</option>
                        <option value="Hives">Hives</option>
                        <option value="Itching">Itching</option>
                        <option value="Shortness of Breath">Shortness of Breath</option>
                        <option value="Vomiting">Vomiting</option>
                        <option value="Others">Others (specify)</option>
                    </select>
                    <input type="text" id="reactionOtherInput" placeholder="Specify Reaction" style="display:none;" />
                </div>
                <div>
                    <select name="severity" required>
                        <option value="">Select Severity</option>
                        <option value="Mild">Mild</option>
                        <option value="Moderate">Moderate</option>
                        <option value="Severe">Severe</option>
                    </select>
                </div>
                <input type="hidden" name="allergen" id="allergenFinal" />
                <input type="hidden" name="reaction" id="reactionFinal" />
                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                <button type="submit" class="add-btn">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Allergy Modal -->
<div id="editAllergyModal" class="custom-modal">
    <div class="modal-content" id="editAllergyModalContent">
        <button type="button" class="close-btn" onclick="closeModal('editAllergyModal')">&times;</button>
        <h3>Edit Allergy</h3>
        <form id="editAllergyForm" method="post" action="update_allergy.php">
            <input type="hidden" name="id" id="editAllergyId" />
            <div class="allergy-form-row">
                <div>
                    <select name="allergen_dropdown" id="editAllergenSelect" required onchange="editToggleOtherInput(this, 'editAllergenOtherInput')">
                        <option value="">Select Allergen</option>
                        <option value="Peanuts">Peanuts</option>
                        <option value="Penicillin">Penicillin</option>
                        <option value="Pollen">Pollen</option>
                        <option value="Shellfish">Shellfish</option>
                        <option value="Eggs">Eggs</option>
                        <option value="Milk">Milk</option>
                        <option value="Latex">Latex</option>
                        <option value="Insect Stings">Insect Stings</option>
                        <option value="Dust Mites">Dust Mites</option>
                        <option value="Mold">Mold</option>
                        <option value="Others">Others (specify)</option>
                    </select>
                    <input type="text" id="editAllergenOtherInput" placeholder="Specify Allergen" style="display:none;" />
                </div>
                <div>
                    <select name="reaction_dropdown" id="editReactionSelect" required onchange="editToggleOtherInput(this, 'editReactionOtherInput')">
                        <option value="">Select Reaction</option>
                        <option value="Rash">Rash</option>
                        <option value="Anaphylaxis">Anaphylaxis</option>
                        <option value="Swelling">Swelling</option>
                        <option value="Hives">Hives</option>
                        <option value="Itching">Itching</option>
                        <option value="Shortness of Breath">Shortness of Breath</option>
                        <option value="Vomiting">Vomiting</option>
                        <option value="Others">Others (specify)</option>
                    </select>
                    <input type="text" id="editReactionOtherInput" placeholder="Specify Reaction" style="display:none;" />
                </div>
                <div>
                    <select name="severity" id="editSeverity" required>
                        <option value="">Select Severity</option>
                        <option value="Mild">Mild</option>
                        <option value="Moderate">Moderate</option>
                        <option value="Severe">Severe</option>
                    </select>
                </div>
                <input type="hidden" name="allergen" id="editAllergenFinal" />
                <input type="hidden" name="reaction" id="editReactionFinal" />
                <input type="hidden" name="patient_id" value="<?= $patient_id ?>" />
                <button type="submit" class="save-btn">Save</button>
            </div>
        </form>
    </div>
</div>
