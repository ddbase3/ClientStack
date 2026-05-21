export function toChildPath(parentPath, key) {
	const parent = parentPath || '$';

	if (typeof key === 'number' || /^\d+$/.test(String(key))) {
		return `${parent}[${key}]`;
	}

	if (/^[A-Za-z_$][A-Za-z0-9_$]*$/.test(key)) {
		return `${parent}.${key}`;
	}

	return `${parent}[${JSON.stringify(String(key))}]`;
}

export function getValueAtPath(value, path) {
	if (!path || path === '$') {
		return value;
	}

	const segments = parsePath(path);
	let current = value;

	for (const segment of segments) {
		if (current == null) {
			return undefined;
		}

		current = current[segment];
	}

	return current;
}

function parsePath(path) {
	const segments = [];
	let index = 1;

	while (index < path.length) {
		if (path[index] === '.') {
			index++;
			const match = /^[A-Za-z_$][A-Za-z0-9_$]*/.exec(path.slice(index));

			if (!match) {
				break;
			}

			segments.push(match[0]);
			index += match[0].length;
			continue;
		}

		if (path[index] === '[') {
			const end = path.indexOf(']', index);

			if (end === -1) {
				break;
			}

			const content = path.slice(index + 1, end);

			if (/^\d+$/.test(content)) {
				segments.push(Number(content));
			} else {
				segments.push(JSON.parse(content));
			}

			index = end + 1;
			continue;
		}

		break;
	}

	return segments;
}
