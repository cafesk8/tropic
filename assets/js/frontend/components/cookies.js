import Register from 'framework/common/utils/Register';
import 'vanilla-cookieconsent';

export default function cookieInit () {
    window.addEventListener('load', function () {
        var cc = initCookieConsent();

        cc.run({
            auto_language: 'document',
            current_lang: 'cs',
            autoclear_cookies: true,
            remove_cookie_tables: true,

            onAccept: function () {
                if (cc.allowedCategory('analytics')) {
                    gtag('consent', 'update', {
                        'analytics_storage': 'granted'
                    });
                }

                if (cc.allowedCategory('ads')) {
                    gtag('consent', 'update', {
                        'ad_storage': 'granted'
                    });
                }
            },

            onChange: function (cookie, changedPreferences) {
                var len = changedPreferences.length;
                for (var i = 0; i < len; i++) {
                    let name = changedPreferences[i];
                    if (cookie.level.includes(name)) {
                        if (name == 'analytics') {
                            gtag('consent', 'update', {
                                'analytics_storage': 'granted'
                            });
                        }
                        if (name == 'ads') {
                            gtag('consent', 'update', {
                                'ad_storage': 'granted'
                            });
                        }
                    } else {
                        if (name == 'analytics') {
                            gtag('consent', 'update', {
                                'analytics_storage': 'denied'
                            });
                        }
                        if (name == 'ads') {
                            gtag('consent', 'update', {
                                'ad_storage': 'denied'
                            });
                        }
                    }
                }
            },

            languages: {
                'cs': {
                    consent_modal: {
                        title: 'Pou????v??me cookies!',
                        description: 'Tato webov?? str??nka pou????v?? nezbytn?? soubory cookies k zaji??t??n?? spr??vn??ho fungov??n??. A d??le dal???? soubory cookies, kter?? slou???? k tomu, abychom pro v??s mohli webov?? str??nky vylep??ovat a optimalizovat. Tato volba bude nastavena a?? po souhlasu.',
                        primary_btn: {
                            text: 'P??ijmout v??e',
                            role: 'accept_all'
                        },
                        secondary_btn: {
                            text: 'Nastaven??'
                        }
                    },
                    settings_modal: {
                        title: 'P??edvolby soubor?? cookies',
                        save_settings_btn: 'Ulo??it nastaven??',
                        accept_all_btn: 'P??ijmout v??e',
                        reject_all_btn: false,
                        close_btn_label: 'Zav????t',
                        blocks: [
                            {
                                title: 'Pou??it?? cookies',
                                description: 'Soubory cookies pou????v??me k zaji??t??n?? z??kladn??ch funkc?? webu a ke zlep??en?? va??eho online z????itku. Pro ka??dou kategorii si m????ete vybrat, zda se chcete p??ihl??sit/odhl??sit, kdykoliv budete cht??t. Ve??ker?? podrobnosti t??kaj??c?? se soubor?? cookies a dal????ch citliv??ch ??daj?? naleznete v ??pln??m zn??n?? z??sad ochrany osobn??ch ??daj??.'
                            },
                            {
                                title: 'Nezbytn?? cookies',
                                description: 'Tyto soubory cookies jsou nezbytn?? pro spr??vn?? fungov??n?? na??ich webov??ch str??nek. Bez t??chto cookies by web nefungoval spr??vn??.',
                                toggle: {
                                    value: 'necessary',
                                    enabled: true,
                                    readonly: true
                                }
                            },
                            {
                                title: 'Soubory cookies pro v??kon a analytiku',
                                description: 'Tyto soubory cookies umo????uj?? webu zapamatovat si nastaven??, kter?? jste provedli v minulosti. Tak?? shroma????uj?? informace o tom, jak web pou????v??te, kter?? str??nky jste nav??t??vili a na kter?? odkazy jste klikli. V??echna data jsou anonymn?? a nelze je pou????t k va???? identifikaci.',
                                toggle: {
                                    value: 'analytics',
                                    enabled: false,
                                    readonly: false
                                }
                            },
                            {
                                title: 'Soubory cookies pro reklamu a c??len??',
                                description: 'Marketingov?? cookies slou???? k pochopen?? chov??n?? u??ivatel?? na na??ich webov??ch str??nk??ch. Tyto cookies se tak?? pou????vaj?? k zobrazov??n?? reklam, kter?? jsou relevantn??j???? pro va??e z??jmy. Tyto informace m????eme sd??let s reklamn??mi platformami, kter?? mohou tak?? ukl??dat informace o chov??n?? u??ivatele z??skan?? p??i proch??zen??. To n??m umo????uje vytvo??it konkr??tn?? profil a zobrazovat reklamy odpov??daj??c?? va??im z??jm??m.',
                                toggle: {
                                    value: 'ads',
                                    enabled: false,
                                    readonly: false
                                }
                            },
                            {
                                title: 'V??ce informac??',
                                description: 'M??te-li jak??koli dotazy ohledn?? na??ich z??sad t??kaj??c??ch se soubor?? cookies nebo dal????ho nastaven??, kontaktujte n??s.'
                            }
                        ]
                    }
                },
                'sk': {
                    consent_modal: {
                        title: 'Pou????vame cookies!',
                        description: 'T??to webov?? str??nka pou????va potrebn?? s??bory cookies k zaisteniu spr??vneho fungovania. A ??al??ie s??bory cookies, ktor?? sl????ia k tomu, aby sme pre v??s mohli webov?? str??nky vylep??ova?? a optimalizova??. T??to vo??ba bude nastaven?? a?? po s??hlase.',
                        primary_btn: {
                            text: 'Prija?? v??etko',
                            role: 'accept_all'
                        },
                        secondary_btn: {
                            text: 'Nastavenia'
                        }
                    },
                    settings_modal: {
                        title: 'Predvo??by s??borov cookies',
                        save_settings_btn: 'Ulo??i?? nastavenia',
                        accept_all_btn: 'Prija?? v??etko',
                        reject_all_btn: false,
                        close_btn_label: 'Zavrie??',
                        blocks: [
                            {
                                title: 'Pou??itie cookies',
                                description: 'S??bory cookies pou????vame k zaisteniu z??kladn??ch funkci?? webu a k zlep??eniu v????ho online z????itku. Pre ka??d?? kateg??riu si m????ete vybra??, ??i sa chcete prihl??si??/odhl??si??, kedyko??vek budete chcie??. V??etky podrobnosti t??kaj??ce sa s??borov cookies a ??al????ch citliv??ch ??dajov n??jdete v ??plnom znen?? z??sad ochrany osobn??ch ??dajov.'
                            },
                            {
                                title: 'Potrebn?? cookies',
                                description: 'Tieto s??bory cookies s?? potrebn?? pre spr??vne fungovanie na??ich webov??ch str??nok. Bez t??chto cookies by web nefungoval spr??vne.',
                                toggle: {
                                    value: 'necessary',
                                    enabled: true,
                                    readonly: true
                                }
                            },
                            {
                                title: 'S??bory cookies pre v??kon a analytiku',
                                description: 'Tieto s??bory cookies umo????uj?? webu zapam??ta?? si nastavenia, ktor?? ste spravili v minulosti. Tie?? zhroma????uj?? inform??cie o tom, ako web pou????vate, ktor?? str??nky ste nav??t??vili a na ktor?? odkazy ste klikli. V??etky d??ta s?? anonymn?? a nie je mo??n?? ich pou??i?? k va??ej identifik??cii.',
                                toggle: {
                                    value: 'analytics',
                                    enabled: false,
                                    readonly: false
                                }
                            },
                            {
                                title: 'S??bory cookies pre reklamu a cielenie',
                                description: 'Marketingov?? cookies sl????ia k pochopeniu chovania u????vate??ov na na??ich webov??ch str??nkach. Tieto cookies sa tie?? pou????vaj?? k zobrazovaniu rekl??m, ktor?? s?? relevantnej??ie pre va??e z??ujmy. Tieto inform??cie m????eme zdie??a?? s reklamn??mi platformami, ktor?? m????u tie?? uklada?? inform??cie o chovan?? u????vate??a z??skan?? pri prech??dzan??. To n??m umo????uje vytvori?? konkr??tny profil a zobrazova?? reklamy odpovedaj??ce va??im z??ujmom.',
                                toggle: {
                                    value: 'ads',
                                    enabled: false,
                                    readonly: false
                                }
                            },
                            {
                                title: 'Viac inform??ci??',
                                description: 'Ak m??te ak??ko??vek ot??zky oh??adne na??ich z??sad t??kaj??cich sa s??borov cookies alebo ??al????ch nastaven??, kontaktujte n??s.'
                            }
                        ]
                    }
                }
            }
        });
    });
}

(new Register()).registerCallback(cookieInit);
