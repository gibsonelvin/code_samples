const express = require('express')

const router = express.Router()
const db = require('db')

const { DataFileType } = db.getModels()
const {
	requireAuthenticated
} = require('middleware')

router.get(
	'/',
	requireAuthenticated,
	async (req, res) => {
		const dataFileTypes = await DataFileType.findAll()
		res.status(200).json(dataFileTypes)
	}
)

module.exports = router
