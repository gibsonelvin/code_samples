const express = require('express')

const router = express.Router()
const db = require('db')

const { Entity, DataRequest, DataRequestConstituent } = db.getModels()

const validator = require('express-joi-validation').createValidator({})
const bodyParser = require('body-parser')
const schemas = require('./schemas')

const entityIdParamValidator = validator.params(schemas.entityId)

const {
	requireAuthenticated
} = require('middleware')

const { ClientError } = require('../utilities')

const jsonBodyParser = bodyParser.json({ strict: true })

router.get(
	'/entity/:entityId/sent/',
	requireAuthenticated,
	entityIdParamValidator,
	async (req, res, next) => {
		const { userAbilities } = res.locals
		const { entityId } = req.params
		if (userAbilities.can().read().oversight({ ownerEntityId: Number(entityId) }).calc()) {
			const dataRequests = await DataRequest.findAll({
				where:
					{ fromEntityId: entityId },
				include: [
					'toEntity',
					'fromEntity'
				]
			})

			return res.status(200).json(dataRequests)
		}

		return next(new ClientError(403))
	}
)

router.get(
	'/entity/:entityId/received',
	requireAuthenticated,
	async (req, res, next) => {
		const { entityId } = req.params
		const { userAbilities } = res.locals
		const entity = await Entity.findOne({ where: { id: entityId } })

		if (userAbilities.can().read().oversight({ ownerEntityId: Number(entityId) }).calc()) {
			const dataRequests = await DataRequest.findAll({
				where:
					{ toEntityId: entity.id },
				include: [
					'toEntity',
					'fromEntity'
				]
			})

			return res.status(200).json(dataRequests)
		}

		return next(new ClientError(403))
	}
)
router.post(
	'/createFrom/:entityId',
	requireAuthenticated,
	entityIdParamValidator,
	express.json({ limit: '1mb' }),
	jsonBodyParser,
	validator.body(schemas.post),
	async (req, res, next) => {
		const requestorEntityId = req.params.entityId
		const { userAbilities } = res.locals

		const { requestedEntityId, constituents } = req.body
		const entity = await Entity.findOne({ where: { id: requestedEntityId } })

		if (!entity) {
			return next(
				new ClientError(404, 'Entity with the provided id does not exist')
			)
		}

		// Check permissions before creating
		if (userAbilities.can().update().oversight({ ownerEntityId: Number(requestorEntityId) }).calc()) {
			let newDataRequest = null
			await db.transaction(async () => {
				newDataRequest = await DataRequest.create({
					description: constituents[0].label + (constituents.length > 1 ? ', ...' : ''),
					status: 0,
					fromEntityId: requestorEntityId,
					toEntityId: requestedEntityId
				})

				for (const constituent of constituents) {
					await DataRequestConstituent.create({
						dataRequestId: Number(newDataRequest.id),
						fileType: Number(constituent.fileType),
						label: constituent.label
					})
				}
			})

			// Fetches new data request with associated entities
			const updatedDataRequest = await DataRequest.findOne({
				where: {
					id: newDataRequest.id
				},
				include: [
					'toEntity',
					'fromEntity'
				]
			})

			return res.status(200).json({ updatedDataRequest })
		}

		return next(new ClientError(403))
	}
)

module.exports = router
