/* 
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */


wysiwyg_element = function () {
    return {
       element_id: "",
        addEvents: function (html_element) {

            generator_events.addIdEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addNameEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addTitleEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addWidthEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addReadonlyEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addClassEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addLabelClassEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addInputClassEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addTooltipEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
            generator_events.addDescriptionEvent(wysiwyg_element, html_element, wysiwyg_element.element_id);
        }
    };
}();
