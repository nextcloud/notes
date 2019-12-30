/**
 * global nextcloud helpers
 */

const closeNavbar = () => {
	const navigationToggle = document.getElementById('app-navigation-toggle')
	const navOpen = document.body.classList.contains('nav-open')

	if (
		navigationToggle
		&& navigationToggle.style.display !== 'none'
		&& navOpen
	) {
		navigationToggle.click()
	}
}

const openNavbar = () => {
	const navigationToggle = document.getElementById('app-navigation-toggle')
	const navOpen = document.body.classList.contains('nav-open')

	if (
		navigationToggle
		&& navigationToggle.style.display !== 'none'
		&& !navOpen
	) {
		navigationToggle.click()
	}
}

export { closeNavbar, openNavbar }
