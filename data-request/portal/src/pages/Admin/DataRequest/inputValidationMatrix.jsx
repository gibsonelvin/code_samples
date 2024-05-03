const MAX_INPUT_LEN = {
	label: 160
}

const inputValidationMatrix = {
	label: (value) => {
		if (!value || value.length > MAX_INPUT_LEN.label) {
			return `Can not be empty or longer than ${MAX_INPUT_LEN.label}`
		}
	},
	file_type: (value) => {
		if (!value) {
			return 'File type can not be empty.'
		}
	}
}

export default inputValidationMatrix
