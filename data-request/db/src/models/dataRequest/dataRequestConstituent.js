const { Model, DataTypes } = require('sequelize')

const schema = {
	fileType: DataTypes.INTEGER,
	label: DataTypes.STRING,
	path: DataTypes.STRING,
	accepted: DataTypes.BOOLEAN
}

class DataRequestConstituent extends Model {
	static associate(models) {
		this.belongsTo(models.DataRequest, { as: 'dataRequest', foreignKey: { name: 'dataRequestId', allowNull: false } })
	}

	static init(options) {
		super.init(schema, { ...options })
	}
}

module.exports = DataRequestConstituent
