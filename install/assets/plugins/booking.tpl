/**
 * Booking
 * 
 * plugin to manage booking 
 *
 * @category 	plugin
 * @version 	1.0.0
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic (m@xim.name)
 * @internal	@properties &model=Model class;text;\modResource &itemTemplates=Booking item templates IDs;text; &priceTv=Price TV name;text;price &dateFormat=Date format (frontend);text;d.m.Y &canceledStatus=Canceled order status ID;text;5 &lexicon=Lexicon (frontend);text;frontend
 * @internal	@events OnPageNotFound,OnCommerceInitialized,OnBeforeOrderProcessing,OnBeforeCartItemAdding,OnCartChanged,OnOrderSaved,OnBeforeOrderHistoryUpdate
 * @internal    @installset base
 */

require MODX_BASE_PATH . 'assets/modules/booking/plugin.booking.php';
