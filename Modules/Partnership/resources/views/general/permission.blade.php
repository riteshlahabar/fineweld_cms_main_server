<tr>
                                                    <td class="group-name">{{ __('partnership::partner.partnership_module') }}</td>
                                                    <td>
                                                        <input class="form-check-input row-select" type="checkbox" id="partnership_module">
                                                        <label for="partnership_module">{{ __('app.select_all') }}</label>
                                                    </td>
                                                    <td>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.create]" id="partner.create">
                                                        <label for="partner.create">{{ __('partnership::partner.create_partner') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.edit]" id="partner.edit">
                                                        <label for="partner.edit">{{ __('partnership::partner.edit_partner') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.view]" id="partner.view">
                                                        <label for="partner.view">{{ __('partnership::partner.view_partners') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.delete]" id="partner.delete">
                                                        <label for="partner.delete">{{ __('partnership::partner.delete_partner') }}</label>
                                                        <br>

                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.contract.create]" id="partner.contract.create">
                                                        <label for="partner.contract.create">{{ __('partnership::contract.create') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.contract.edit]" id="partner.contract.edit">
                                                        <label for="partner.contract.edit">{{ __('partnership::contract.edit') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.contract.view]" id="partner.contract.view">
                                                        <label for="partner.contract.view">{{ __('partnership::contract.view') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.contract.delete]" id="partner.contract.delete">
                                                        <label for="partner.contract.delete">{{ __('partnership::contract.delete') }}</label>
                                                        <br>

                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.settlement.create]" id="partner.settlement.create">
                                                        <label for="partner.settlement.create">{{ __('partnership::partner.create_settlement') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.settlement.edit]" id="partner.settlement.edit">
                                                        <label for="partner.settlement.edit">{{ __('partnership::partner.edit_settlement') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.settlement.view]" id="partner.settlement.view">
                                                        <label for="partner.settlement.view">{{ __('partnership::partner.view_settlement') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.settlement.delete]" id="partner.settlement.delete">
                                                        <label for="partner.settlement.delete">{{ __('partnership::partner.delete_settlement') }}</label>
                                                        <br>

                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.payment-allocation.view]" id="partner.payment-allocation.view">
                                                        <label for="partner.payment-allocation.view">{{ __('partnership::partner.party_payment_allocation_to_partner') }}</label>
                                                        <br>

                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.payment-allocation.delete]" id="partner.payment-allocation.delete">
                                                        <label for="partner.payment-allocation.delete">{{ __('partnership::partner.delete_party_payment_allocation_to_partner') }}</label>
                                                        <br>
                                                        <input class="form-check-input partnership_module_p" type="checkbox" name="permission[partner.report]" id="partner.report">
                                                        <label for="partner.report">{{ __('partnership::partner.partnership_report') }}</label>
                                                        <br>



                                                    </td>
                                                </tr>
