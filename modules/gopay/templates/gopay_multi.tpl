{*
* 2007-2011 PrestaShop 
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author GoPay <integrace@gopay.cz>
*  @version  Release: $Revision: 1.4 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<!-- rozsirena varianta platby (multi choice) -->



{foreach from=$paymentMethods item=item}
	
  	<p class="payment_module">
		<a href="javascript:$('#gopay_form_gopay_{$item.code}').submit();" title="{l s='Zaplatit Gopay' mod='gopay'}">
			<img src="{$item.logo}" alt="{l s='Zaplatit Gopay' mod='gopay'}" />
			{l s='Zaplatit Gopay' mod='gopay'}
			{$item.title}
		</a>
	</p>
	
	<form action="{$payUrl}" method="post" id="gopay_form_gopay_{$item.code}" class="hidden">
		<input type="text" name="cartId" value="{$cartId}" />
		<input type="text" name="param" value="&paymentChannel={$item.code}" />
		<input type="text" name="{$item.code}" value="{$item.code}" />
		{foreach from=$paymentMethods item=item}
		<input type="text" name="method_gopay_{$item.code}" value="method_gopay_{$item.code}" /> 	
		{/foreach}
	</form>
	
{/foreach}