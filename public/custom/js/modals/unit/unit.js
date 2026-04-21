$(function() {
	"use strict";

    let originalButtonText;

    let openModal = $('#unitModal');

    let baseUnit = $('select[name="base_unit_id"]');

    let secondaryUnit = $('select[name="secondary_unit_id"]');

    let baseUnitValue = $("input[name='base_unit_value']");

    let secondaryUnitValue = $("input[name='secondary_unit_value']");

    let conversionRate = $("input[name='conversion_rate']");
    
    //let initialValue = 1;

    /**
     * Language
     * */
    const _lang = {
                pleaseSelectBaseUnit : "Please Select Base Unit!",
                pleaseSelectSecondaryUnit : "Please Select Secondary Unit!",
                enterConvertionRate : "Please Enter Conversion Rate!",
                enterBaseUnitValue : "Please enter base unit value greater than 0!",
                enterSecondaryUnitValue : "Please enter secondary unit value greater than 0!",
            };

    function toPositiveFloat(value, fallback = 0) {
        const parsed = parseFloat(value);
        if (Number.isNaN(parsed) || parsed <= 0) {
            return fallback;
        }
        return parsed;
    }

    function formatValue(value) {
        const parsed = parseFloat(value);
        if (Number.isNaN(parsed) || !Number.isFinite(parsed)) {
            return '';
        }
        // Keep values readable and stable in UI.
        return parseFloat(parsed.toFixed(6)).toString();
    }

    function calculateAndSetConversionRate() {
        const baseValue = toPositiveFloat(baseUnitValue.val(), 0);
        const secondaryValue = toPositiveFloat(secondaryUnitValue.val(), 0);

        if (baseValue <= 0 || secondaryValue <= 0) {
            return false;
        }

        if (baseUnit.val() === secondaryUnit.val()) {
            secondaryUnitValue.val(formatValue(baseValue));
            conversionRate.val('1');
            return true;
        }

        const rate = secondaryValue / baseValue;
        if (!Number.isFinite(rate) || rate <= 0) {
            return false;
        }

        conversionRate.val(formatValue(rate));

        return true;
    }

    window.autoSetDefaultUnits = function(_baseUnitId = '', _secondaryUnitId = '', _conversionRate = 1) {
        if(_baseUnitId != ''){
            baseUnit.val(_baseUnitId);
        }
        if(_secondaryUnitId != ''){
            secondaryUnit.val(_secondaryUnitId);
        }

        const rate = toPositiveFloat(_conversionRate, 1);
        conversionRate.val(formatValue(rate));
        baseUnitValue.val('1');
        secondaryUnitValue.val(formatValue(rate));

        showUnitData();
        functionSetLabel();
    };

    function getBaseUnitData(){
        return baseUnit.find("option:selected").text();
    }

    function getSecondaryUnitData(){
        return secondaryUnit.find("option:selected").text();
    }
    function showUnitData() {
        $("#base-text").text(`${getBaseUnitData()}`);
        $("#secondary-text").text(`${getSecondaryUnitData()}`);
    }

    function functionSetLabel(){
        const baseValue = formatValue(toPositiveFloat(baseUnitValue.val(), 1));
        const secondaryValue = formatValue(toPositiveFloat(secondaryUnitValue.val(), 1));
        var label = `${baseValue} ${getBaseUnitData()} = ${secondaryValue} ${getSecondaryUnitData()}`;
        $(".unit-label").text(label);
    }

    $(document).ready(function() {
        autoSetDefaultUnits();
        showUnitData();
        calculateAndSetConversionRate();
        functionSetLabel();
    });

    $(document).on("change", "select[name='base_unit_id'], select[name='secondary_unit_id']", function(){
        // Same unit means 1:1 conversion by definition.
        if (baseUnit.val() === secondaryUnit.val()) {
            const baseValue = toPositiveFloat(baseUnitValue.val(), 1);
            secondaryUnitValue.val(formatValue(baseValue));
        }
        calculateAndSetConversionRate();
        showUnitData();
        functionSetLabel();
    });

    $(document).on("input", "input[name='base_unit_value'], input[name='secondary_unit_value']", function(){
        calculateAndSetConversionRate();
        functionSetLabel();
    });

    function validateUnitsAndConversionRate() {
        if(baseUnit.val().length === 0){
            iziToast.error({title: 'Error', layout: 2, message: _lang.pleaseSelectBaseUnit});
            return false;
        }
        if(secondaryUnit.val().length === 0){
            iziToast.error({title: 'Error', layout: 2, message: _lang.pleaseSelectSecondaryUnit});
            return false;
        }
        if(toPositiveFloat(baseUnitValue.val(), 0) <= 0){
            iziToast.error({title: 'Error', layout: 2, message: _lang.enterBaseUnitValue});
            return false;
        }
        if(toPositiveFloat(secondaryUnitValue.val(), 0) <= 0){
            iziToast.error({title: 'Error', layout: 2, message: _lang.enterSecondaryUnitValue});
            return false;
        }
        if(!calculateAndSetConversionRate()){
            iziToast.error({title: 'Error', layout: 2, message: _lang.enterConvertionRate});
            return false;
        }
        if(conversionRate.val().trim().length === 0 || parseFloat(conversionRate.val()) <=0 ){
            iziToast.error({title: 'Error', layout: 2, message: _lang.enterConvertionRate});
            return false;
        }
        return true;
    }
    $(".setUnits").on('click', function() {
        setUnits();
    });
    window.setUnits = function(){
        let validated = validateUnitsAndConversionRate();
        if(validated){
            openModal.modal('hide');
            showUnitData();
            functionSetLabel();
        }
    }

});//main function
