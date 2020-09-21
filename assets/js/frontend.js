import './jQuery/registerJquery';

import tooltip from 'framework/common/bootstrap/tooltip';

import 'framework/common/components';

import CustomizeBundle from 'framework/common/validation/customizeBundle';
import { showFormErrorsWindow, findOrCreateErrorList } from './frontend/utils/customizeBundle';

import './frontend/utils/loginWindowOpener';
import './frontend/utils/watchDogWindowOpener';

import './loadTranslations';

import './frontend/components';

import './frontend/validation/form';

// HP entry?
import './frontend/homepage/slickInit';

import './frontend/deliveryAddress';

// order entry?
import './frontend/order';

// product entry?
import './frontend/product';

import './frontend/cart/cartBox';

// cart entry?
import './frontend/cart';

import 'framework/common/validation/customizeFpValidator';
import './frontend/validation/validationInit';
import 'framework/common/validation';

import './frontend/blog/blogList';

import Register from 'framework/common/utils/Register';
const $ = window.jQuery || global.jQuery || jQuery;
tooltip($);

CustomizeBundle.showFormErrorsWindow = showFormErrorsWindow;
CustomizeBundle.findOrCreateErrorList = findOrCreateErrorList;

$(document).ready(function () {
    const register = new Register();
    register.registerNewContent($('body'));
});

$(window).on('popstate', function (event) {
    const state = event.originalEvent.state;
    if (state && state.hasOwnProperty('refreshOnPopstate') && state.refreshOnPopstate === true) {
        location.reload();
    }
});
