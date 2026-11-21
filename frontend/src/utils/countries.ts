// football-data da los países en inglés y a su manera ("Congo DR", "Korea, South"), así que
// se traducen a mano al código que usa flag-icons. Los que no estén aquí salen sin bandera.
const codes: Record<string, string> = {
  afghanistan: 'af', albania: 'al', algeria: 'dz', andorra: 'ad', angola: 'ao', 'antigua and barbuda': 'ag',
  argentina: 'ar', armenia: 'am', aruba: 'aw', australia: 'au', austria: 'at', azerbaijan: 'az', bahamas: 'bs',
  bahrain: 'bh', bangladesh: 'bd', barbados: 'bb', belarus: 'by', belgium: 'be', belize: 'bz', benin: 'bj',
  bermuda: 'bm', bolivia: 'bo', 'bosnia-herzegovina': 'ba', 'bosnia and herzegovina': 'ba', botswana: 'bw',
  brazil: 'br', bulgaria: 'bg', 'burkina faso': 'bf', burundi: 'bi', 'cape verde': 'cv', 'cape verde islands': 'cv',
  cambodia: 'kh', cameroon: 'cm', canada: 'ca', 'central african republic': 'cf', chad: 'td', chile: 'cl',
  china: 'cn', 'china pr': 'cn', colombia: 'co', comoros: 'km', congo: 'cg', 'congo dr': 'cd', 'dr congo': 'cd',
  'costa rica': 'cr', croatia: 'hr', cuba: 'cu', curacao: 'cw', 'curaçao': 'cw', cyprus: 'cy',
  'czech republic': 'cz', czechia: 'cz', denmark: 'dk', 'dominican republic': 'do', ecuador: 'ec', egypt: 'eg',
  'el salvador': 'sv', england: 'gb-eng', 'equatorial guinea': 'gq', eritrea: 'er', estonia: 'ee', ethiopia: 'et',
  'faroe islands': 'fo', finland: 'fi', france: 'fr', gabon: 'ga', gambia: 'gm', georgia: 'ge', germany: 'de',
  ghana: 'gh', greece: 'gr', grenada: 'gd', guadeloupe: 'gp', guatemala: 'gt', guinea: 'gn', 'guinea-bissau': 'gw',
  guyana: 'gy', haiti: 'ht', honduras: 'hn', hungary: 'hu', iceland: 'is', india: 'in', indonesia: 'id', iran: 'ir',
  iraq: 'iq', ireland: 'ie', israel: 'il', italy: 'it', 'ivory coast': 'ci', "côte d'ivoire": 'ci', jamaica: 'jm',
  japan: 'jp', jordan: 'jo', kazakhstan: 'kz', kenya: 'ke', 'korea republic': 'kr', 'korea, south': 'kr',
  'south korea': 'kr', 'korea dpr': 'kp', 'north korea': 'kp', kosovo: 'xk', kuwait: 'kw', kyrgyzstan: 'kg',
  latvia: 'lv', lebanon: 'lb', liberia: 'lr', libya: 'ly', liechtenstein: 'li', lithuania: 'lt', luxembourg: 'lu',
  madagascar: 'mg', malawi: 'mw', malaysia: 'my', mali: 'ml', malta: 'mt', martinique: 'mq', mauritania: 'mr',
  mexico: 'mx', moldova: 'md', montenegro: 'me', morocco: 'ma', mozambique: 'mz', namibia: 'na', netherlands: 'nl',
  'new zealand': 'nz', nicaragua: 'ni', niger: 'ne', nigeria: 'ng', 'north macedonia': 'mk', macedonia: 'mk',
  'northern ireland': 'gb-nir', norway: 'no', oman: 'om', pakistan: 'pk', palestine: 'ps', panama: 'pa',
  paraguay: 'py', peru: 'pe', philippines: 'ph', poland: 'pl', portugal: 'pt', 'puerto rico': 'pr', qatar: 'qa',
  romania: 'ro', russia: 'ru', rwanda: 'rw', 'saudi arabia': 'sa', scotland: 'gb-sct', senegal: 'sn', serbia: 'rs',
  'sierra leone': 'sl', singapore: 'sg', slovakia: 'sk', slovenia: 'si', somalia: 'so', 'south africa': 'za',
  'south sudan': 'ss', spain: 'es', 'sri lanka': 'lk', sudan: 'sd', suriname: 'sr', sweden: 'se',
  switzerland: 'ch', syria: 'sy', tanzania: 'tz', thailand: 'th', togo: 'tg', 'trinidad and tobago': 'tt',
  tunisia: 'tn', turkey: 'tr', 'türkiye': 'tr', turkmenistan: 'tm', uganda: 'ug', ukraine: 'ua',
  'united arab emirates': 'ae', 'united states': 'us', usa: 'us', uruguay: 'uy', uzbekistan: 'uz',
  venezuela: 've', vietnam: 'vn', wales: 'gb-wls', yemen: 'ye', zambia: 'zm', zimbabwe: 'zw',
  // las áreas de las ligas que no son un país
  europe: 'eu', world: 'un',
}

export function countryCode(name: string | null | undefined) {
  return name ? codes[name.trim().toLowerCase()] : undefined
}
