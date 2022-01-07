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
                        title: 'Používáme cookies!',
                        description: 'Tato webová stránka používá nezbytné soubory cookies k zajištění správného fungování. A dále další soubory cookies, které slouží k tomu, abychom pro vás mohli webové stránky vylepšovat a optimalizovat. Tato volba bude nastavena až po souhlasu.',
                        primary_btn: {
                            text: 'Přijmout vše',
                            role: 'accept_all'
                        },
                        secondary_btn: {
                            text: 'Nastavení'
                        }
                    },
                    settings_modal: {
                        title: 'Předvolby souborů cookies',
                        save_settings_btn: 'Uložit nastavení',
                        accept_all_btn: 'Přijmout vše',
                        reject_all_btn: false,
                        close_btn_label: 'Zavřít',
                        blocks: [
                            {
                                title: 'Použití cookies',
                                description: 'Soubory cookies používáme k zajištění základních funkcí webu a ke zlepšení vašeho online zážitku. Pro každou kategorii si můžete vybrat, zda se chcete přihlásit/odhlásit, kdykoliv budete chtít. Veškeré podrobnosti týkající se souborů cookies a dalších citlivých údajů naleznete v úplném znění zásad ochrany osobních údajů.'
                            },
                            {
                                title: 'Nezbytné cookies',
                                description: 'Tyto soubory cookies jsou nezbytné pro správné fungování našich webových stránek. Bez těchto cookies by web nefungoval správně.',
                                toggle: {
                                    value: 'necessary',
                                    enabled: true,
                                    readonly: true
                                }
                            },
                            {
                                title: 'Soubory cookies pro výkon a analytiku',
                                description: 'Tyto soubory cookies umožňují webu zapamatovat si nastavení, které jste provedli v minulosti. Také shromažďují informace o tom, jak web používáte, které stránky jste navštívili a na které odkazy jste klikli. Všechna data jsou anonymní a nelze je použít k vaší identifikaci.',
                                toggle: {
                                    value: 'analytics',
                                    enabled: false,
                                    readonly: false
                                }
                            },
                            {
                                title: 'Soubory cookies pro reklamu a cílení',
                                description: 'Marketingové cookies slouží k pochopení chování uživatelů na našich webových stránkách. Tyto cookies se také používají k zobrazování reklam, které jsou relevantnější pro vaše zájmy. Tyto informace můžeme sdílet s reklamními platformami, které mohou také ukládat informace o chování uživatele získané při procházení. To nám umožňuje vytvořit konkrétní profil a zobrazovat reklamy odpovídající vašim zájmům.',
                                toggle: {
                                    value: 'ads',
                                    enabled: false,
                                    readonly: false
                                }
                            },
                            {
                                title: 'Více informací',
                                description: 'Máte-li jakékoli dotazy ohledně našich zásad týkajících se souborů cookies nebo dalšího nastavení, kontaktujte nás.'
                            }
                        ]
                    }
                },
                'sk': {
                    consent_modal: {
                        title: 'Používame cookies!',
                        description: 'Táto webová stránka používa potrebné súbory cookies k zaisteniu správneho fungovania. A ďalšie súbory cookies, ktoré slúžia k tomu, aby sme pre vás mohli webové stránky vylepšovať a optimalizovať. Táto voľba bude nastavená až po súhlase.',
                        primary_btn: {
                            text: 'Prijať všetko',
                            role: 'accept_all'
                        },
                        secondary_btn: {
                            text: 'Nastavenia'
                        }
                    },
                    settings_modal: {
                        title: 'Predvoľby súborov cookies',
                        save_settings_btn: 'Uložiť nastavenia',
                        accept_all_btn: 'Prijať všetko',
                        reject_all_btn: false,
                        close_btn_label: 'Zavrieť',
                        blocks: [
                            {
                                title: 'Použitie cookies',
                                description: 'Súbory cookies používame k zaisteniu základných funkcií webu a k zlepšeniu vášho online zážitku. Pre každú kategóriu si môžete vybrať, či sa chcete prihlásiť/odhlásiť, kedykoľvek budete chcieť. Všetky podrobnosti týkajúce sa súborov cookies a ďalších citlivých údajov nájdete v úplnom znení zásad ochrany osobných údajov.'
                            },
                            {
                                title: 'Potrebné cookies',
                                description: 'Tieto súbory cookies sú potrebné pre správne fungovanie našich webových stránok. Bez týchto cookies by web nefungoval správne.',
                                toggle: {
                                    value: 'necessary',
                                    enabled: true,
                                    readonly: true
                                }
                            },
                            {
                                title: 'Súbory cookies pre výkon a analytiku',
                                description: 'Tieto súbory cookies umožňujú webu zapamätať si nastavenia, ktoré ste spravili v minulosti. Tiež zhromažďujú informácie o tom, ako web používate, ktoré stránky ste navštívili a na ktoré odkazy ste klikli. Všetky dáta sú anonymné a nie je možné ich použiť k vašej identifikácii.',
                                toggle: {
                                    value: 'analytics',
                                    enabled: false,
                                    readonly: false
                                }
                            },
                            {
                                title: 'Súbory cookies pre reklamu a cielenie',
                                description: 'Marketingové cookies slúžia k pochopeniu chovania užívateľov na našich webových stránkach. Tieto cookies sa tiež používajú k zobrazovaniu reklám, ktoré sú relevantnejšie pre vaše záujmy. Tieto informácie môžeme zdieľať s reklamnými platformami, ktoré môžu tiež ukladať informácie o chovaní užívateľa získané pri prechádzaní. To nám umožňuje vytvoriť konkrétny profil a zobrazovať reklamy odpovedajúce vašim záujmom.',
                                toggle: {
                                    value: 'ads',
                                    enabled: false,
                                    readonly: false
                                }
                            },
                            {
                                title: 'Viac informácií',
                                description: 'Ak máte akékoľvek otázky ohľadne našich zásad týkajúcich sa súborov cookies alebo ďalších nastavení, kontaktujte nás.'
                            }
                        ]
                    }
                }
            }
        });
    });
}

(new Register()).registerCallback(cookieInit);
