# Translate

`Translate` is a plugin for [FreshRSS](https://github.com/FreshRSS/FreshRSS) that translates feed titles and content to your chosen target language. Users can select from DeeplX, Google Translate, or LibreTranslate services.

This is a fork of [jacob2826's TranslateTitlesCN](https://github.com/jacob2826/FreshRSS-TranslateTitlesCN), that translates to your language of choice.
- **Target language selection** - Translate to any language, not just Chinese
- **Content translation** - Translate article content in addition to titles

## Screenshots

<img src="img/2025-10-06_21-46.png" width="300px"/>
<img src="img/2025-10-06_21-43.png" width="300px"/>
<img src="img/2025-10-06_21-45.png" width="300px"/>

## Installation

1. Download the `Translate` plugin
2. Place the `xTranslate` folder in your FreshRSS instance's `./extensions` 
3. Log in to your FreshRSS instance
4. Navigate to the Extensions section in the admin panel
5. Find `Translate` in the plugin list and click "Enable"

## Usage

After installation and activation, go to the plugin's configuration page:

### Translation Service Selection

Choose from three translation providers:

- **DeeplX**: 
  - Deploy your own [DeeplX](https://github.com/OwO-Network/DeepLX/) instance
  - Default API address: `http://localhost:1188/translate`

- **Google Translate**: 
  - No additional configuration required
  - Uses Google's free translation API

- **LibreTranslate**: 
  - Deploy your own [LibreTranslate](https://github.com/LibreTranslate/LibreTranslate) instance for offline translation
  - Or use public instances (see [LibreTranslate Mirrors](https://github.com/LibreTranslate/LibreTranslate#mirrors))

### Configuration Options

- **Target Language**: Select your desired target language for translations
- **Per-Feed Translation**: Enable/disable title translation for each feed individually
- **Content Translation**: Enable/disable content translation for each feed individually

## Notes

- When using DeeplX, ensure your instance is properly deployed and the API address is correct
- Use translation services responsibly to avoid rate limiting or IP bans

## License

This project is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html).

## Credits

- Original project: [jacob2826/FreshRSS-TranslateTitlesCN](https://github.com/jacob2826/FreshRSS-TranslateTitlesCN)
