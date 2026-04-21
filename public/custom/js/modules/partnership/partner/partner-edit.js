$(function() {
   "use strict";

   $(document).ready(function() {

    /**
     * variables: _opening_balance_type
     * File: Variables defined in views/party/edit.blade.php
     * */
    setOpeningBalanceTypeRadio(_opening_balance_type);

   });
   /**
    * Set Tracking Type Radio button
    * */
   function setOpeningBalanceTypeRadio(_opening_balance_type) {
       if(_opening_balance_type == 'to_pay'){
        $("#to_pay").attr('checked', true);
       }else{
        $("#to_receive").attr('checked', true);
       }
   }

});
