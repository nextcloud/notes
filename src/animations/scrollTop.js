export default (el, scrollDuration) => {
	if (!el) return

	const scrollStep = -el.scrollTop / (scrollDuration / 15)

	const scrollInterval = setInterval(function() {
		if (el.scrollY > 0) {
			el.scrollBy(0, scrollStep)
		} else {
			el.scrollTop = 0
			clearInterval(scrollInterval)
		}
	}, 15)
}
