/* 
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */

textarea_element = function () {
    return {
        element_id: "",
        addEvents: function (html_element) {

            generator_events.addIdEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addNameEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addTitleEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addWidthEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addReadonlyEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addClassEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addLabelClassEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addInputClassEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addTooltipEvent(textarea_element, html_element, textarea_element.element_id);
            generator_events.addDescriptionEvent(textarea_element, html_element, textarea_element.element_id);
        }
    };
}();

