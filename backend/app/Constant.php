<?php

namespace App;

class Constant {

    const NOT_ARCHIVE = '1';
    const ARCHIVE = '0';

    const ACTIVE = '1';
    const INACTIVE = '0';

    /** payment type **/
    const ONLINE = '0';
    const OFFLINE = '1';

    /** checkout type **/
    const GUEST_CHECKOUT = '1';
    const USER_CHECKOUT = '0';

    /** slot **/
    const MORNING = '2';
    const AFTERNOON = '1';
    const EVENING = '0';

    /** order **/
    const NO = '0';
    const YES = '1';
    const CANCEL = '2';

    const OUR_SERVICE_SEO_ID = '1';
    const SERVICE_CENTER_SEO_ID = '2';
    const ACCESSORIES_SEO_ID = '3';
    const ABOUT_US_SEO_ID = '4';
    const OFFERS_SEO_ID = '5';
}