import Ajax from 'framework/common/utils/Ajax';
import Register from 'framework/common/utils/Register';
import Window from '../utils/Window';
import Translator from 'bazinga-translator';
import CustomizeBundle from 'framework/common/validation/customizeBundle';

const contactFormSelector = 'form[name="contact_form"]';

export default class ContactForm {

    ajaxSubmit (event, contactForm) {
        event.preventDefault();

        if (CustomizeBundle.isFormValid($('form[name="contact_form"]')) === false) {
            return;
        }

        Ajax.ajax({
            loaderElement: 'body',
            url: $(event.currentTarget).attr('action'),
            method: 'post',
            data: $(event.currentTarget).serialize(),
            success: contactForm.onSuccess
        });
    }
    onSuccess (data) {

        if (data['success'] === false) {
            // eslint-disable-next-line no-new
            new Window({
                content: data.message,
                buttonCancel: true,
                textCancel: Translator.trans('Close')
            });
        } else {
            // eslint-disable-next-line no-new
            new Window({
                content: data.message,
                buttonCancel: true,
                textCancel: Translator.trans('Close')
            });
        }
    }

    static init ($container) {
        const contactForm = new ContactForm();
        $container.filterAllNodes(contactFormSelector)
            .on('submit', (event) => contactForm.ajaxSubmit(event, contactForm));
    }
}

(new Register()).registerCallback(ContactForm.init, 'ContactForm.init');
