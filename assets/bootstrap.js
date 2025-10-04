import { startStimulusApp } from '@symfony/stimulus-bundle';
import CsrfProtectionController from './controllers/csrf_protection_controller.js';
import HelloController from './controllers/hello_controller.js';

const app = startStimulusApp();
app.register('csrf-protection', CsrfProtectionController);
app.register('hello', HelloController);

// Charger automatiquement les controllers de ./controllers
// const modules = import.meta.glob("./controllers/**/*.js") // Removed problematic line
// for (const path in modules) {
//     modules[path]().then((module) => {
//         const controllerName = path
//             .replace("./controllers/", "")
//             .replace(".js", "")
//         // Éviter de réenregistrer 'chart' si déjà fait
//         if (controllerName !== 'chart') {
//             app.register(controllerName, module.default)
//         }
//     })
// }
