const joi = require('joi')

const entityId = joi.object({ entityId: joi.number().integer().required() })
const post = joi.object({
	requestedEntityId: joi.number().integer().required(),
	constituents: joi.array().items(joi.object({
		label: joi.string().required(),
		fileType: joi.number().integer().required()
	})).required()
})

module.exports = {
	entityId,
	post
}
