const joi = require('joi')

const id = joi.object({ id: joi.number().required() })
const constituentId = joi.object({ constituentId: joi.number().required() })
const dataFile = joi.object({
	file: joi.string().required(),
	fileMeta: {
		name: joi.string().required(),
		type: joi.string().required(),
		size: joi.number().required()
	}
}).required()

module.exports = {
	id,
	constituentId,
	dataFile
}
