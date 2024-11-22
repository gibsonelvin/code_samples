const express = require('express')

const router = express.Router()
const db = require('db')

const { DataFileType, DataRequest, DataRequestConstituent } = db.getModels()

const validator = require('express-joi-validation').createValidator({})
const schemas = require('./schemas')
const { s3 } = require('../../processor')

const idParamValidator = validator.params(schemas.id)
const constituentIdParamValidator = validator.params(schemas.constituentId)
const {
	requireAuthenticated
} = require('../../middleware')

// const { ClientError, enums } = require('../../utilities')
const { enums } = require('../../utilities')

const { DATA_REQUEST } = enums

router.get(
	'/dataRequest/:id',
	idParamValidator,
	requireAuthenticated,
	async (req, res) => {
		const { userAbilities } = res.locals
		const dataRequestId = req.params.id

		const dataRequest = await DataRequest.findOne({
			where: {
				id: dataRequestId
			}
		})
		if (!dataRequest) {
			return res.status(404).json({ message: 'This doesn\'t make any sense... Data Request not found!' })
			// return new ClientError(404, 'This doesn\'t make any sense... Data Request not found!')
		}

		if (userAbilities.can().read().oversight({ ownerEntityId: Number(dataRequest.toEntityId) }).calc()
			|| userAbilities.can().read().oversight({ ownerEntityId: Number(dataRequest.fromEntityId) }).calc()
		) {
			const dataRequestConstituents = await DataRequestConstituent.findAll({ order: [['createdAt', 'ASC']], where: { dataRequestId } })
			return res.status(200).json(dataRequestConstituents)
		}

		return res.status(403).json({ message: 'Unauthorized action.' })
		// return new ClientError(403)
	}
)

router.patch(
	'/reject/:constituentId',
	constituentIdParamValidator,
	requireAuthenticated,
	async (req, res) => {
		const { userAbilities } = res.locals
		const constituentId = req.params.constituentId
		const constituent = await DataRequestConstituent.findOne({
			where: {
				id: constituentId
			}
		})
		if (!constituent) {
			return res.status(404).json({ message: 'Data Request Constituent not found' })
			// return new ClientError(404, 'Data Request Constituent not found')
		}

		const updatedDataRequest = await DataRequest.findOne({
			where: {
				id: constituent.dataRequestId
			},
			include: [
				'fromEntity',
				'toEntity'
			]
		})

		// Check permissions before updating
		if (userAbilities.can().update().oversight({ ownerEntityId: Number(updatedDataRequest.toEntityId) }).calc()
			|| userAbilities.can().update().oversight({ ownerEntityId: Number(updatedDataRequest.fromEntityId) }).calc()
		) {
			await db.transaction(async () => {
				await constituent.update({
					accepted: false
				})

				const newConstituentProperties = { ...constituent.dataValues }
				newConstituentProperties.id = null
				newConstituentProperties.accepted = null
				newConstituentProperties.path = null
				await DataRequestConstituent.create({ ...newConstituentProperties })

				await updatedDataRequest.update({
					status: DATA_REQUEST.STATUS.RE_REQUESTED
				})
			})

			return res.status(200).json({ updatedDataRequest })
		}

		return res.status(403).json({ message: 'Unauthorized action.' })
		// return new ClientError(403)
	}
)

router.patch(
	'/accept/:constituentId',
	constituentIdParamValidator,
	requireAuthenticated,
	async (req, res) => {
		const { userAbilities } = res.locals
		const constituentId = req.params.constituentId

		const constituent = await DataRequestConstituent.findOne({
			where: {
				id: constituentId
			}
		})

		if (!constituent) {
			return res.status(404).json({ message: 'Data Request Constituent not found' })
			// return new ClientError(404, 'Data Request Constituent not found')
		}

		const dataRequest = await DataRequest.findOne({
			where: {
				id: constituent.dataRequestId
			},
			include: [
				'toEntity',
				'fromEntity'
			]
		})

		if (!dataRequest) {
			return res.status(404).json({ message: 'Associated Data Request not found' })
			// return new ClientError(404, 'Data Request Constituent not found')
		}

		// Check permissions before updating
		if (userAbilities.can().update().oversight({ ownerEntityId: Number(dataRequest.toEntityId) }).calc()
			|| userAbilities.can().update().oversight({ ownerEntityId: Number(dataRequest.fromEntityId) }).calc()
		) {
			await db.transaction(async () => {
				await constituent.update({
					accepted: true
				})

				if (dataRequest.status === DATA_REQUEST.STATUS.ADDRESSED) {
					const unaddressedConstituents = await DataRequestConstituent.findAll({
						where: {
							dataRequestId: constituent.dataRequestId,
							accepted: null
						}
					})
					if (unaddressedConstituents.length === 0) {
						dataRequest.status = DATA_REQUEST.STATUS.ACCEPTED
						await dataRequest.save()
					}
				}
			})

			// Makes status readable for API return
			// updatedDataRequestTransaction.status = DATA_REQUEST.STATUS_TEXT[dataRequest.status]

			return res.status(200).json({ updatedDataRequest: dataRequest })
		}

		return res.status(403).json({ message: 'Unauthorized action.' })
		// return new ClientError(403)
	}
)

router.post(
	'/attach/:constituentId',
	requireAuthenticated,
	constituentIdParamValidator,
	express.json({ limit: '100mb' }),
	validator.body(schemas.dataFile),
	async (req, res) => {
		const { userAbilities } = res.locals
		const { constituentId } = req.params

		const { file, fileMeta } = req.body
		// Update validator for post data

		const name = fileMeta.name
		const fileExtension = name.substr(name.lastIndexOf('.') + 1)

		const constituent = await DataRequestConstituent.findOne({
			where: { id: constituentId }
		})

		if (!constituent) {
			return res.status(404).json({ message: 'Data Request Constituent not found, unable to attach file.' })
			// return new ClientError(404, 'Data Request Constituent not found, unable to attach file.')
		}

		const updatedDataRequest = await DataRequest.findOne({
			where: {
				id: constituent.dataRequestId
			},
			include: [
				'toEntity',
				'fromEntity'
			]
		})

		// Check permissions before updating
		if (userAbilities.can().update().oversight({ ownerEntityId: Number(updatedDataRequest.toEntityId) }).calc()
			|| userAbilities.can().update().oversight({ ownerEntityId: Number(updatedDataRequest.fromEntityId) }).calc()
		) {
			const dataFileType = await DataFileType.findOne({
				where: {
					id: constituent.fileType
				}
			})

			if (!dataFileType) {
				return res.status(404).json({ message: 'Data file type specified is invalid, unable to attach file.' })
				// return new ClientError(404, 'Data file type specified is invalid, unable to attach file.')
			}

			const extensions = dataFileType.extensions.split(',')
			let validExtension = false
			for (let extension of extensions) {
				extension = (extension.length > 1 ? extension.substr(1) : extension)
				if (extension === fileExtension || extension === '*') {
					validExtension = true
				}
			}

			if (!validExtension) {
				return res.status(422).json({ message: `Invalid extension, please upload a file with the following extension(s): ${extensions}` })
				/* return new ClientError(
					422,
					'Upload Failed',
					`Invalid extension, please upload a file with the following extension(s): ${extensions}`,
					'Invalid extension!'
				) */
			}

			const url = await s3.fileAttachment(name, fileExtension, file, null)

			if (url.error) {
				return res.status(422).json({ message: 'We were unable to upload the file. This is likely not your fault. Please try again later' })
				/* return new ClientError(
					422,
					'Upload Failed',
					'We were unable to upload the file. This is likely not your fault. Please try again later',
					url.error
				) */
			}

			const updatedDataRequestTransaction = await db.transaction(async () => {
				await constituent.update({
					path: url
				})

				const unaddressedConstituents = await DataRequestConstituent.findAll({
					where: {
						dataRequestId: constituent.dataRequestId,
						path: null
					}
				})

				if (unaddressedConstituents.length === 0) {
					await updatedDataRequest.update({
						status: DATA_REQUEST.STATUS.ADDRESSED
					})
				}
				return updatedDataRequest
			})

			return res.status(200).json({ updatedDataRequest: updatedDataRequestTransaction })
		}

		return res.status(403).json({ message: 'You do not have permission for the requested action.' })
		// return new ClientError(403, 'You do not have permission for the requested action.')
	}
)

module.exports = router
