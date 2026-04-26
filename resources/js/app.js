import './bootstrap';

const scrollToTarget = (targetId) => {
	const target = document.getElementById(targetId);

	if (!target) {
		return false;
	}

	target.scrollIntoView({ behavior: 'smooth', block: 'start' });
	target.focus({ preventScroll: true });

	return true;
};

document.addEventListener('click', (event) => {
	const link = event.target.closest('[data-scroll-target]');

	if (!link) {
		return;
	}

	const targetId = link.getAttribute('data-scroll-target');

	if (targetId === null) {
		return;
	}

	if (scrollToTarget(targetId)) {
		event.preventDefault();
	}
});

window.addEventListener('DOMContentLoaded', () => {
	if (window.location.hash.length <= 1) {
		return;
	}

	const targetId = window.location.hash.substring(1);
	scrollToTarget(targetId);
});
