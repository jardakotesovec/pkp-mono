/**
 * @file js/load.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Compiler entry point for building the JavaScript package. File imports
 *  using the `@` symbol are aliased to `lib/ui-library/src`.
 */
import PkpLoad from '../lib/pkp/js/load.js';

// Import controllers used by OMP
import Container from '@/components/Container/Container.vue';
import CatalogContainer from '@/components/Container/CatalogContainer.vue';
import SettingsContainer from '@/components/Container/SettingsContainer.vue';

// Expose Vue, the registry and controllers in a global var
window.pkp = Object.assign(PkpLoad, {
	controllers: {
		Container,
		CatalogContainer,
		SettingsContainer
	}
});
