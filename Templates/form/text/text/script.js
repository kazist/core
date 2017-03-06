/* 
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */

text_element = function () {
    return {
        element_id: "",
        addEvents: function (html_element) {

            generator_events.addIdEvent(text_element, html_element, text_element.element_id);
            generator_events.addNameEvent(text_element, html_element, text_element.element_id);
            generator_events.addTitleEvent(text_element, html_element, text_element.element_id);
            generator_events.addWidthEvent(text_element, html_element, text_element.element_id);
            generator_events.addReadonlyEvent(text_element, html_element, text_element.element_id);
            generator_events.addClassEvent(text_element, html_element, text_element.element_id);
            generator_events.addLabelClassEvent(text_element, html_element, text_element.element_id);
            generator_events.addInputClassEvent(text_element, html_element, text_element.element_id);
            generator_events.addTooltipEvent(text_element, html_element, text_element.element_id);
            generator_events.addDescriptionEvent(text_element, html_element, text_element.element_id);
        }
    };
}();