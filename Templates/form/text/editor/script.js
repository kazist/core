/* 
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */

editor_element = function () {
    return {
        element_id: "",
        addEvents: function (html_element) {
            // alert('element_id');

            generator_events.addIdEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addNameEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addTitleEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addWidthEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addReadonlyEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addClassEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addLabelClassEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addInputClassEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addTooltipEvent(editor_element, html_element, editor_element.element_id);
            generator_events.addDescriptionEvent(editor_element, html_element, editor_element.element_id);
            
           // console.log(generatorEvents.elements[editor_element.element_id]);
        }
    };
}();
